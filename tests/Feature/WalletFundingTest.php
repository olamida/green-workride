<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletFundingTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.webhook_secret' => self::SECRET]);
    }

    private function signedPayload(array $payload): array
    {
        $raw = json_encode($payload);

        return [$raw, hash_hmac('sha512', (string) $raw, self::SECRET)];
    }

    public function test_webhook_credits_cash_balance_on_charge_success(): void
    {
        $user = User::factory()->create();
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'id' => 123456,
                'reference' => "WR-{$user->id}-TOPUP1",
                'amount' => 50000,
            ],
        ];
        [$raw, $signature] = $this->signedPayload($payload);

        $response = $this->postJson('/paystack/webhook', $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertOk();

        $wallet = Wallet::where('user_id', $user->id)->first();

        $this->assertEquals(500.00, (float) $wallet->cash_balance);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'type' => TransactionType::TopUp->value,
            'amount' => 500.00,
            'tx_ref' => "WR-{$user->id}-TOPUP1",
            'gateway_ref' => '123456',
        ]);
    }

    public function test_webhook_is_idempotent_for_duplicate_events(): void
    {
        $user = User::factory()->create();
        $payload = [
            'event' => 'charge.success',
            'data' => ['id' => 1, 'reference' => "WR-{$user->id}-TOPUP2", 'amount' => 30000],
        ];
        [$raw, $signature] = $this->signedPayload($payload);

        $this->postJson('/paystack/webhook', $payload, ['x-paystack-signature' => $signature])->assertOk();
        $this->postJson('/paystack/webhook', $payload, ['x-paystack-signature' => $signature])->assertOk();

        $this->assertEquals(300.00, (float) Wallet::where('user_id', $user->id)->first()->cash_balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $user = User::factory()->create();
        $payload = [
            'event' => 'charge.success',
            'data' => ['id' => 1, 'reference' => "WR-{$user->id}-TOPUP3", 'amount' => 30000],
        ];

        $this->postJson('/paystack/webhook', $payload, [
            'x-paystack-signature' => 'forged-signature',
        ])->assertStatus(400);

        $this->assertDatabaseMissing('transactions', ['type' => TransactionType::TopUp->value]);
    }

    public function test_webhook_ignores_non_charge_events(): void
    {
        $user = User::factory()->create();
        $payload = [
            'event' => 'charge.failed',
            'data' => ['id' => 1, 'reference' => "WR-{$user->id}-TOPUP4", 'amount' => 30000],
        ];
        [$raw, $signature] = $this->signedPayload($payload);

        $this->postJson('/paystack/webhook', $payload, ['x-paystack-signature' => $signature])->assertOk();

        $this->assertDatabaseMissing('transactions', ['type' => TransactionType::TopUp->value]);
    }

    public function test_webhook_rejects_unknown_reference_format(): void
    {
        $user = User::factory()->create();
        $payload = [
            'event' => 'charge.success',
            'data' => ['id' => 1, 'reference' => 'NOPE-NOT-OURS', 'amount' => 30000],
        ];
        [$raw, $signature] = $this->signedPayload($payload);

        $this->postJson('/paystack/webhook', $payload, ['x-paystack-signature' => $signature])
            ->assertStatus(400);

        $this->assertDatabaseMissing('transactions', ['type' => TransactionType::TopUp->value]);
    }

    public function test_webhook_rejects_unknown_user(): void
    {
        $payload = [
            'event' => 'charge.success',
            'data' => ['id' => 1, 'reference' => 'WR-999999-TOPUP5', 'amount' => 30000],
        ];
        [$raw, $signature] = $this->signedPayload($payload);

        $this->postJson('/paystack/webhook', $payload, ['x-paystack-signature' => $signature])
            ->assertStatus(400);

        $this->assertDatabaseMissing('transactions', ['type' => TransactionType::TopUp->value]);
    }
}
