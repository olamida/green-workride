<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarnedWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('workride.time_bank.enabled', true);
    }

    private function driver(): User
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $driver->id]);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function bookableTrip(?User $driver = null, float $fare = 600): Trip
    {
        $driver = $driver ?? $this->driver();

        return Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'fare_per_seat' => $fare,
            'total_seats' => 4,
            'available_seats' => 4,
            'departure_time' => now()->addMinutes(30),
        ]);
    }

    private function cashPassenger(float $balance = 2000): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id, 'cash_balance' => $balance]);

        return $user;
    }

    public function test_driver_earns_fare_minus_commission_union_and_insurance_on_capture(): void
    {
        $driver = $this->driver();
        $trip = $this->bookableTrip($driver, 600);
        $passenger = $this->cashPassenger();

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $booking = $trip->bookings()->where('passenger_id', $passenger->id)->firstOrFail();

        $this->actingAs($driver)->post("/bookings/{$booking->id}/board")->assertRedirect();

        // 600 - 60 (10% commission) - 30 (5% union fee) - 100 insurance = 410
        $this->assertSame('410.00', $driver->wallet->fresh()->earned_balance);
        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::Earned->value,
            'reference' => "EARN-{$booking->id}",
            'amount' => 410,
        ]);
    }

    public function test_earning_is_idempotent_on_double_settle(): void
    {
        $driver = $this->driver();
        $trip = $this->bookableTrip($driver, 600);
        $passenger = $this->cashPassenger();

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $booking = $trip->bookings()->where('passenger_id', $passenger->id)->firstOrFail();

        $this->actingAs($driver)->post("/bookings/{$booking->id}/board")->assertRedirect();
        $this->actingAs($driver)->post("/trips/{$trip->id}/start")->assertRedirect();
        $this->actingAs($driver)->post("/trips/{$trip->id}/complete")->assertRedirect();

        $this->assertSame('410.00', $driver->wallet->fresh()->earned_balance);
        $this->assertSame(
            1,
            Transaction::where('reference', "EARN-{$booking->id}")->count()
        );
    }

    public function test_no_earning_credited_when_time_bank_disabled(): void
    {
        config()->set('workride.time_bank.enabled', false);

        $driver = $this->driver();
        $trip = $this->bookableTrip($driver, 600);
        $passenger = $this->cashPassenger();

        $this->actingAs($passenger)->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])->assertRedirect();
        $booking = $trip->bookings()->where('passenger_id', $passenger->id)->firstOrFail();

        $this->actingAs($driver)->post("/bookings/{$booking->id}/board")->assertRedirect();

        $this->assertSame('0.00', $driver->wallet->fresh()->earned_balance);
    }

    public function test_withdraw_debits_earned_first_then_cash(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => 2000,
            'earned_balance' => 5000,
        ]);

        $this->actingAs($user)
            ->post('/wallet/withdraw', [
                'amount' => 6000,
                'bank_code' => '044',
                'account_number' => '0123456789',
            ])
            ->assertRedirect();

        $wallet = $user->wallet->fresh();
        $this->assertSame('0.00', $wallet->earned_balance);
        $this->assertSame('1000.00', $wallet->cash_balance);

        $this->assertDatabaseHas('payouts', [
            'wallet_id' => $wallet->id,
            'amount' => 6000,
            'status' => 'completed',
        ]);
    }

    public function test_withdraw_never_uses_subsidy_credits(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'subsidy_credits' => 5000]);

        $this->actingAs($user)
            ->post('/wallet/withdraw', [
                'amount' => 1000,
                'bank_code' => '044',
                'account_number' => '0123456789',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('5000.00', $user->wallet->fresh()->subsidy_credits);
    }

    public function test_withdraw_validates_minimum_amount(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'earned_balance' => 500]);

        $this->actingAs($user)
            ->post('/wallet/withdraw', [
                'amount' => 50,
                'bank_code' => '044',
                'account_number' => '0123456789',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_api_wallet_index_includes_earned_balance(): void
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => 1500,
            'earned_balance' => 900,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('cash_balance', 1500)
            ->assertJsonPath('earned_balance', 900)
            ->assertJsonPath('subsidy_credits', 0);
    }

    public function test_api_withdraw_requires_authentication_and_validates(): void
    {
        $this->postJson('/api/v1/wallet/withdraw', [])->assertStatus(401);

        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'earned_balance' => 500]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/wallet/withdraw', [
                'amount' => 50,
                'bank_code' => '',
                'account_number' => '',
            ])
            ->assertStatus(422);
    }
}
