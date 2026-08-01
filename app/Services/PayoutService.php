<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Enums\TransactionType;
use App\Models\Payout;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Driver earnings withdrawal to a bank account.
 *
 * Debits earned_balance first, then cash_balance — never subsidy credits.
 * The Moniepoint transfer is mocked for now (marked completed immediately,
 * recorded in the payouts ledger for later reconciliation).
 */
class PayoutService
{
    public function __construct(private WalletService $wallets) {}

    public function withdraw(User $user, float $amount, string $bankCode, string $accountNumber): Payout
    {
        if (! config('workride.time_bank.enabled')) {
            throw ValidationException::withMessages(['wallet' => 'Withdrawals are not enabled yet.']);
        }

        $amount = round($amount, 2);
        $minimum = (float) config('workride.payout.min_amount', 100);
        $maximum = (float) config('workride.payout.max_amount', 100000);

        if ($amount < $minimum) {
            throw ValidationException::withMessages(['amount' => 'Minimum withdrawal is ₦'.number_format($minimum, 0).'.']);
        }

        if ($amount > $maximum) {
            throw ValidationException::withMessages(['amount' => 'Maximum withdrawal is ₦'.number_format($maximum, 0).'.']);
        }

        if ($bankCode === '' || $accountNumber === '') {
            throw ValidationException::withMessages(['account_number' => 'Bank code and account number are required.']);
        }

        return DB::transaction(function () use ($user, $amount, $bankCode, $accountNumber) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet) {
                throw ValidationException::withMessages(['amount' => 'Insufficient balance to withdraw.']);
            }

            $spendable = round((float) $wallet->earned_balance + (float) $wallet->cash_balance, 2);

            if ($spendable < $amount) {
                throw ValidationException::withMessages(['amount' => 'Insufficient balance to withdraw.']);
            }

            // Earned first, then cash — subsidy is never withdrawable.
            $earned = round(min((float) $wallet->earned_balance, $amount), 2);
            $cash = round($amount - $earned, 2);

            $reference = 'PO-'.$user->id.'-'.strtoupper(Str::random(12));

            $this->wallets->debitForTransfer($wallet, $cash, $earned, $reference, TransactionType::Payout, 'Withdrawal to account ****'.substr($accountNumber, -4), [
                'bank_code' => $bankCode,
                'account_number' => $accountNumber,
                'debited' => ['earned' => $earned, 'cash' => $cash],
            ]);

            $payout = Payout::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'bank_code' => $bankCode,
                'account_number' => $accountNumber,
                'status' => PayoutStatus::Pending,
                'reference' => $reference,
                'meta' => ['provider' => 'moniepoint', 'mock' => true, 'debited' => ['earned' => $earned, 'cash' => $cash]],
            ]);

            // Mock Moniepoint transfer — settles instantly until a real
            // provider integration is configured.
            $payout->update(['status' => PayoutStatus::Completed]);

            return $payout->fresh();
        });
    }
}
