<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class P2pTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('workride.time_bank.enabled', true);
    }

    private function user(
        float $cash = 0,
        float $earned = 0,
        float $subsidy = 0,
        int $level = VerificationLevel::WorkplaceVerified->value,
        ?string $phone = null,
    ): User {
        $user = User::factory()->create([
            'verification_level' => $level,
            'phone' => $phone ?? fake()->unique()->numerify('080########'),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => $cash,
            'earned_balance' => $earned,
            'subsidy_credits' => $subsidy,
        ]);

        return $user;
    }

    public function test_p2p_disabled_when_time_bank_off(): void
    {
        config()->set('workride.time_bank.enabled', false);

        $sender = $this->user(10000);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 1000,
                'type' => 'cash',
            ])
            ->assertStatus(422);
    }

    public function test_cash_transfer_applies_one_percent_fee(): void
    {
        $sender = $this->user(10000);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 1000,
                'type' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('transfer.amount', 1000);

        $this->assertSame('8990.00', $sender->wallet->fresh()->cash_balance); // 1000 + 10 fee
        $this->assertSame('1000.00', $receiver->wallet->fresh()->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::Fee->value,
            'amount' => 10,
        ]);
    }

    public function test_earned_transfer_is_free_and_credits_receiver_earned(): void
    {
        $sender = $this->user(0, 5000);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 2000,
                'type' => 'earned',
            ])
            ->assertCreated()
            ->assertJsonPath('transfer.fee', 0);

        $this->assertSame('3000.00', $sender->wallet->fresh()->earned_balance);
        $this->assertSame('2000.00', $receiver->wallet->fresh()->earned_balance);
        $this->assertSame('0.00', $receiver->wallet->fresh()->cash_balance);
    }

    public function test_receiver_must_be_verified_worker(): void
    {
        $sender = $this->user(10000);
        $receiver = $this->user(level: VerificationLevel::Unverified->value);

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 1000,
                'type' => 'cash',
            ])
            ->assertStatus(422);
    }

    public function test_sender_needs_nin_for_amounts_above_threshold(): void
    {
        $sender = $this->user(10000, level: VerificationLevel::WorkplaceVerified->value);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 6000,
                'type' => 'cash',
            ])
            ->assertStatus(422);
    }

    public function test_daily_transfer_limit_is_enforced(): void
    {
        config()->set('workride.p2p.daily_limit', 10000);

        $sender = $this->user(50000, level: VerificationLevel::NinVerified->value);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 6000,
                'type' => 'cash',
            ])
            ->assertCreated();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 5000,
                'type' => 'cash',
            ])
            ->assertStatus(422);
    }

    public function test_subsidy_credits_are_never_transferable(): void
    {
        $sender = $this->user(cash: 0, subsidy: 5000);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 1000,
                'type' => 'cash',
            ])
            ->assertStatus(422);

        $this->assertSame('5000.00', $sender->wallet->fresh()->subsidy_credits);
        $this->assertSame('0.00', $receiver->wallet->fresh()->cash_balance);
    }

    public function test_transfer_history_lists_sent_transfers(): void
    {
        $sender = $this->user(0, 5000);
        $receiver = $this->user();

        $this->actingAs($sender, 'sanctum')
            ->postJson('/api/v1/wallet/transfer', [
                'receiver_phone' => $receiver->phone,
                'amount' => 1500,
                'type' => 'earned',
            ])
            ->assertCreated();

        $this->actingAs($sender, 'sanctum')
            ->getJson('/api/v1/wallet/transfers')
            ->assertOk()
            ->assertJsonCount(1, 'transfers')
            ->assertJsonPath('transfers.0.amount', 1500)
            ->assertJsonPath('transfers.0.type', 'earned')
            ->assertJsonPath('transfers.0.receiver.id', $receiver->id);
    }
}
