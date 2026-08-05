<?php

namespace Database\Seeders;

use App\Enums\P2pTransferStatus;
use App\Enums\P2pTransferType;
use App\Enums\PayoutStatus;
use App\Enums\TransactionType;
use App\Models\P2pTransfer;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Wallet movement between demo colleagues (guide §14 + Sprint 3.5): 40 P2P
 * transfers (cash with a 1% fee + earned, free) and 20 driver payouts
 * (Moniepoint-mocked). Writes the matching debit/credit/fee transactions so
 * the wallet page, P2P history and Business dashboard fee/payout KPIs demo.
 */
class RichTransferSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichTransferSeeder.');

            return;
        }

        $users = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->get();

        if ($users->isEmpty()) {
            $this->command?->error('RichTransferSeeder needs demo users first.');

            return;
        }

        // --- 40 P2P transfers. ---
        $created = 0;
        foreach (range(1, 40) as $i) {
            $sender = $users[$i % $users->count()];
            $receiver = $users[($i * 7) % $users->count()];

            if ($sender->id === $receiver->id) {
                $receiver = $users[($i * 7 + 1) % $users->count()];
            }

            $isEarned = $i % 3 === 0;
            $amount = 500 + ($i * 173) % 4500;
            $fee = $isEarned ? 0 : max(10.0, round($amount * 0.01, 2));

            $transfer = P2pTransfer::updateOrCreate(
                ['reference' => $this->demoReference('P2P', $i)],
                [
                    'sender_wallet_id' => $sender->wallet?->id ?? Wallet::updateOrCreate(['user_id' => $sender->id], [])->id,
                    'receiver_user_id' => $receiver->id,
                    'amount' => $amount,
                    'fee' => $fee,
                    'type' => $isEarned ? P2pTransferType::Earned : P2pTransferType::Cash,
                    'status' => P2pTransferStatus::Completed,
                    'meta' => ['demo' => true, 'note' => 'Lunch money', 'sent_at' => now()->subDays($i % 10)->toIso8601String()],
                ]
            );

            $senderWalletId = $transfer->sender_wallet_id;
            $receiverWalletId = $receiver->wallet?->id ?? Wallet::updateOrCreate(['user_id' => $receiver->id], [])->id;

            Transaction::updateOrCreate(['reference' => $this->demoReference('P2P', $i).'-DEBIT'], [
                'wallet_id' => $senderWalletId,
                'type' => TransactionType::P2pDebit,
                'amount' => $amount + $fee,
                'description' => 'Transfer to '.$receiver->name,
                'meta' => ['transfer_id' => $transfer->id, 'demo' => true],
            ]);

            Transaction::updateOrCreate(['reference' => $this->demoReference('P2P', $i).'-CREDIT'], [
                'wallet_id' => $receiverWalletId,
                'type' => TransactionType::P2pCredit,
                'amount' => $amount,
                'description' => 'Transfer from '.$sender->name,
                'meta' => ['transfer_id' => $transfer->id, 'demo' => true],
            ]);

            if ($fee > 0) {
                Transaction::updateOrCreate(['reference' => $this->demoReference('P2P', $i).'-FEE'], [
                    'wallet_id' => $senderWalletId,
                    'type' => TransactionType::Fee,
                    'amount' => $fee,
                    'description' => 'P2P transfer fee',
                    'meta' => ['transfer_id' => $transfer->id, 'demo' => true],
                ]);
            }
            $created++;
        }

        // --- 20 driver payouts. ---
        $payoutCreated = 0;
        $l3 = $users->filter(fn (User $u) => (int) $u->verification_level->value >= 3)->values();

        foreach (range(1, 20) as $i) {
            $driver = $l3[$i % $l3->count()];
            $wallet = $driver->wallet ?? Wallet::updateOrCreate(['user_id' => $driver->id], []);
            $amount = 2000 + ($i * 311) % 15000;
            $status = match ($i % 5) {
                4 => PayoutStatus::Pending,
                3 => PayoutStatus::Failed,
                default => PayoutStatus::Completed,
            };

            $payout = Payout::updateOrCreate(
                ['reference' => $this->demoReference('PO', $i)],
                [
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'bank_code' => '058',
                    'account_number' => sprintf('00%08d', 10000000 + $i),
                    'status' => $status,
                    'meta' => ['demo' => true, 'bank' => 'GTBank', 'account_name' => $driver->name],
                ]
            );

            Transaction::updateOrCreate(['reference' => $this->demoReference('PO', $i).'-WDR'], [
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Payout,
                'amount' => $amount,
                'description' => 'Withdrawal to GTBank '.$payout->account_number,
                'meta' => ['payout_id' => $payout->id, 'demo' => true],
            ]);
            $payoutCreated++;
        }

        $this->command?->info(sprintf('Rich demo transfers seeded: %d P2P + %d driver payouts.', $created, $payoutCreated));
    }
}
