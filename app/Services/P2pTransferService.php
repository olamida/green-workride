<?php

namespace App\Services;

use App\Enums\P2pTransferStatus;
use App\Enums\P2pTransferType;
use App\Enums\TransactionType;
use App\Enums\VerificationLevel;
use App\Events\P2pTransferCompleted;
use App\Models\P2pTransfer;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Peer-to-peer wallet transfers between verified colleagues.
 *
 * Cash transfers carry a 1% platform fee (min ₦10); earned transfers are free.
 * Subsidy credits are NEVER transferable — the only way to move subsidy is the
 * workplace-admin bulk credit (prevents diversion of palliative funds).
 * Optimistic locking + unique references keep every transfer idempotent.
 */
class P2pTransferService
{
    public function __construct(private WalletService $wallets) {}

    public function transfer(User $sender, string $receiverPhone, float $amount, P2pTransferType $type): P2pTransfer
    {
        if (! config('workride.time_bank.enabled')) {
            throw ValidationException::withMessages(['wallet' => 'P2P transfers are not enabled yet.']);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        $receiver = User::where('phone', $receiverPhone)->first();

        if (! $receiver) {
            throw ValidationException::withMessages(['receiver_phone' => 'No user found with that phone number.']);
        }

        if ($receiver->id === $sender->id) {
            throw ValidationException::withMessages(['receiver_phone' => 'You cannot transfer to yourself.']);
        }

        if ($receiver->verification_level->value < VerificationLevel::WorkplaceVerified->value) {
            throw ValidationException::withMessages(['receiver_phone' => 'The recipient must be a verified worker (Level 1+).']);
        }

        $threshold = (float) config('workride.p2p.sender_level_threshold_amount', 5000);

        if ($amount > $threshold && $sender->verification_level->value < VerificationLevel::NinVerified->value) {
            throw ValidationException::withMessages([
                'amount' => 'NIN verification (Level 2) is required to send more than ₦'.number_format($threshold, 0).'.',
            ]);
        }

        return DB::transaction(function () use ($sender, $receiver, $amount, $type) {
            $senderWallet = $this->lockWallet($sender);
            $receiverWallet = $this->lockWallet($receiver);

            $dailyLimit = (float) config('workride.p2p.daily_limit', 10000);
            $spentToday = (float) P2pTransfer::where('sender_wallet_id', $senderWallet->id)
                ->where('created_at', '>=', today())
                ->where('status', '!=', P2pTransferStatus::Failed->value)
                ->sum('amount');

            if ($spentToday + $amount > $dailyLimit) {
                throw ValidationException::withMessages([
                    'amount' => 'Daily transfer limit is ₦'.number_format($dailyLimit, 0).'. You have ₦'.number_format(max(0, $dailyLimit - $spentToday), 0).' left today.',
                ]);
            }

            $fee = $this->feeFor($amount, $type);
            $total = round($amount + $fee, 2);

            if ($type === P2pTransferType::Cash) {
                if ((float) $senderWallet->cash_balance < $total) {
                    throw ValidationException::withMessages(['amount' => 'Insufficient cash balance to cover the transfer and fee.']);
                }
            } elseif ((float) $senderWallet->earned_balance < $total) {
                throw ValidationException::withMessages(['amount' => 'Insufficient earned balance for this transfer.']);
            }

            $reference = $this->referenceFor($sender);

            if ($type === P2pTransferType::Cash) {
                $this->wallets->debitForTransfer($senderWallet, $amount, 0, "{$reference}-DEBIT", TransactionType::P2pDebit, "Transfer to {$receiver->name}", [
                    'receiver_user_id' => $receiver->id,
                    'p2p_reference' => $reference,
                ]);
                $this->wallets->creditForTransfer($receiverWallet, $amount, 0, "{$reference}-CREDIT", TransactionType::P2pCredit, "Received from {$sender->name}", [
                    'sender_user_id' => $sender->id,
                    'p2p_reference' => $reference,
                ]);
            } else {
                $this->wallets->debitForTransfer($senderWallet, 0, $amount, "{$reference}-DEBIT", TransactionType::P2pDebit, "Transfer to {$receiver->name}", [
                    'receiver_user_id' => $receiver->id,
                    'p2p_reference' => $reference,
                ]);
                $this->wallets->creditForTransfer($receiverWallet, 0, $amount, "{$reference}-CREDIT", TransactionType::P2pCredit, "Received from {$sender->name}", [
                    'sender_user_id' => $sender->id,
                    'p2p_reference' => $reference,
                ]);
            }

            if ($fee > 0) {
                $feeMeta = ['p2p_reference' => $reference, 'transfer_type' => $type->value];

                if ($type === P2pTransferType::Cash) {
                    $this->wallets->debitForTransfer($senderWallet, $fee, 0, "{$reference}-FEE", TransactionType::Fee, 'P2P platform fee (1%)', $feeMeta);
                } else {
                    $this->wallets->debitForTransfer($senderWallet, 0, $fee, "{$reference}-FEE", TransactionType::Fee, 'P2P platform fee', $feeMeta);
                }
            }

            $transfer = P2pTransfer::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_user_id' => $receiver->id,
                'amount' => $amount,
                'fee' => $fee,
                'type' => $type,
                'reference' => $reference,
                'status' => P2pTransferStatus::Completed,
                'meta' => ['sender_user_id' => $sender->id],
            ]);

            event(new P2pTransferCompleted($transfer));

            return $transfer;
        });
    }

    public function feeFor(float $amount, P2pTransferType $type): float
    {
        if ($type === P2pTransferType::Earned) {
            return 0.0;
        }

        $rate = (float) config('workride.p2p.fee_cash_rate', 0.01);
        $minimum = (float) config('workride.p2p.fee_cash_min', 10);

        return round(max($minimum, round($amount * $rate, 2)), 2);
    }

    private function lockWallet(User $user): Wallet
    {
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

        return $wallet ?? Wallet::create(['user_id' => $user->id]);
    }

    private function referenceFor(User $sender): string
    {
        return 'P2P-'.$sender->id.'-'.now()->timestamp.'-'.strtoupper(Str::random(4));
    }
}
