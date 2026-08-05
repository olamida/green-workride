<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Wallet state for the rich demo base (guide §14 dual + earned balance).
 * Every demo user gets a wallet with plausible cash / subsidy / earned
 * balances and a transaction trail (top-ups, subsidy credits, a few P2P /
 * holds / refunds) so the wallet page, receipts and Business dashboard all
 * light up. References are deterministic and unique.
 */
class RichWalletSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichWalletSeeder.');

            return;
        }

        $users = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->get();

        $txn = 0;
        foreach ($users as $i => $user) {
            $level = (int) $user->verification_level->value;
            $isDriver = $level >= 3;

            $cash = match (true) {
                $isDriver => 2500 + ($i * 731) % 12000,
                $user->role->value === 'volunteer' => 500 + ($i * 97) % 1500,
                default => 1200 + ($i * 173) % 9000,
            };

            $subsidy = match (true) {
                $isDriver => 0,
                $user->role->value === 'workplace_admin' => 20000 + ($i * 500) % 20000,
                default => 3000 + ($i * 907) % 40000,
            };

            $earned = $isDriver ? (700 + ($i * 211) % 8000) : 0;

            $wallet = Wallet::updateOrCreate(['user_id' => $user->id], [
                'cash_balance' => $cash,
                'subsidy_credits' => $subsidy,
                'earned_balance' => $earned,
                'cash_collected_log' => $isDriver ? (200 + ($i * 37) % 3000) : 0,
                'version' => 1,
            ]);

            // Top-up trail (cash).
            if ($cash > 0) {
                $txn++;
                Transaction::updateOrCreate(['reference' => $this->demoReference('TOPUP', $txn)], [
                    'wallet_id' => $wallet->id,
                    'type' => TransactionType::TopUp,
                    'amount' => $cash,
                    'description' => 'Paystack top-up (demo)',
                    'meta' => ['payment_method' => PaymentMethod::Paystack->value, 'demo' => true],
                    'created_at' => now()->subDays(3 + $i % 20),
                ]);
            }

            // Subsidy credit trail.
            if ($subsidy > 0) {
                $txn++;
                Transaction::updateOrCreate(['reference' => $this->demoReference('SUB', $txn)], [
                    'wallet_id' => $wallet->id,
                    'type' => TransactionType::Subsidy,
                    'amount' => $subsidy,
                    'description' => 'MDA palliative subsidy credit (demo)',
                    'meta' => ['demo' => true],
                    'created_at' => now()->subDays(6 + $i % 25),
                ]);
            }

            // Earned-credit trail for drivers (Time-Bank earnings settlement).
            if ($earned > 0) {
                $txn++;
                Transaction::updateOrCreate(['reference' => $this->demoReference('EARN', $txn)], [
                    'wallet_id' => $wallet->id,
                    'type' => TransactionType::Earned,
                    'amount' => $earned,
                    'description' => 'Driver earnings settlement (demo)',
                    'meta' => ['demo' => true],
                    'created_at' => now()->subDays(1 + $i % 15),
                ]);
            }
        }

        $this->command?->info(sprintf('Rich demo wallets seeded: %d wallets + %d transactions.', $users->count(), $txn));
    }
}
