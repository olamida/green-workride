<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTopUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_wallet(): void
    {
        $this->get('/wallet')->assertRedirect('/login');
    }

    public function test_user_can_view_wallet_with_balances(): void
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => 1000,
            'subsidy_credits' => 2500,
        ]);

        $this->actingAs($user)
            ->get('/wallet')
            ->assertOk()
            ->assertSee('My cash balance')
            ->assertSee('1,000.00')
            ->assertSee('2,500.00');
    }

    public function test_top_up_errors_when_paystack_unconfigured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/wallet/topup', ['amount' => 5000])
            ->assertSessionHasErrors('amount');
    }

    public function test_top_up_validates_amount(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/wallet/topup', ['amount' => 10])
            ->assertSessionHasErrors('amount');
    }

    public function test_api_wallet_requires_authentication(): void
    {
        $this->getJson('/api/v1/wallet')->assertStatus(401);
    }

    public function test_api_wallet_index_returns_balances(): void
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => 1500,
            'subsidy_credits' => 0,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('cash_balance', 1500);
    }

    public function test_api_top_up_unconfigured_returns_503(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallet/topup', ['amount' => 5000])
            ->assertStatus(503);
    }
}
