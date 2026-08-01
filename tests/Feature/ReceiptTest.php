<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function paidBooking(?User $passenger = null, ?User $driver = null): Booking
    {
        $passenger ??= User::factory()->create();
        $driver ??= User::factory()->create();

        $trip = Trip::factory()->forDriver($driver)->create();

        return Booking::factory()->create([
            'passenger_id' => $passenger->id,
            'trip_id' => $trip->id,
            'status' => BookingStatus::Completed,
            'fare_paid' => 600,
            'payment_method' => PaymentMethod::Wallet,
        ]);
    }

    public function test_booking_receipt_requires_auth(): void
    {
        $booking = $this->paidBooking();

        $this->get("/receipts/booking/{$booking->id}")->assertRedirect('/login');
    }

    public function test_passenger_can_view_own_booking_receipt(): void
    {
        $passenger = User::factory()->create();
        $booking = $this->paidBooking(passenger: $passenger);

        $this->actingAs($passenger)
            ->get("/receipts/booking/{$booking->id}")
            ->assertOk()
            ->assertSee('TRIP BOOKING RECEIPT')
            ->assertSee('600.00')
            ->assertSee('BK-'.$booking->id)
            ->assertSee('data:image/svg+xml;base64');
    }

    public function test_booking_receipt_forbidden_for_stranger(): void
    {
        $booking = $this->paidBooking();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("/receipts/booking/{$booking->id}")
            ->assertForbidden();
    }

    public function test_driver_can_view_own_booking_receipt(): void
    {
        $driver = User::factory()->create();
        $booking = $this->paidBooking(driver: $driver);

        $this->actingAs($driver)
            ->get("/receipts/booking/{$booking->id}")
            ->assertOk();
    }

    public function test_driver_can_view_earnings_receipt(): void
    {
        $driver = User::factory()->create();
        $booking = $this->paidBooking(driver: $driver);

        $this->actingAs($driver)
            ->get("/receipts/earnings/{$booking->id}")
            ->assertOk()
            ->assertSee('DRIVER EARNINGS RECEIPT')
            ->assertSee('Net earning')
            ->assertSee('EARN-'.$booking->id);
    }

    public function test_earnings_receipt_forbidden_for_non_driver(): void
    {
        $booking = $this->paidBooking();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get("/receipts/earnings/{$booking->id}")
            ->assertForbidden();
    }

    public function test_wallet_owner_can_view_topup_receipt(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::TopUp,
            'amount' => 5000,
            'reference' => 'TOPUP-WR-1-TEST',
            'tx_ref' => 'WR-1-TEST',
        ]);

        $this->actingAs($user)
            ->get("/receipts/topup/{$transaction->id}")
            ->assertOk()
            ->assertSee('WALLET TOP-UP RECEIPT')
            ->assertSee('5,000.00')
            ->assertSee('Paystack');
    }

    public function test_topup_receipt_forbidden_for_stranger(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::TopUp,
            'amount' => 5000,
            'reference' => 'TOPUP-WR-1-TEST',
            'tx_ref' => 'WR-1-TEST',
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/receipts/topup/{$transaction->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_subsidy_receipt(): void
    {
        $workplace = Workplace::factory()->create();
        $user = User::factory()->create(['workplace_id' => $workplace->id]);
        $wallet = Wallet::create(['user_id' => $user->id]);
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Subsidy,
            'amount' => 2000,
            'reference' => 'MDA-1-2026-0001',
        ]);

        $this->actingAs($this->admin())
            ->get("/receipts/subsidy/{$transaction->id}")
            ->assertOk()
            ->assertSee('SUBSIDY CREDIT RECEIPT')
            ->assertSee('2,000.00')
            ->assertSee($workplace->name);
    }

    public function test_subsidy_receipt_forbidden_for_non_admin(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Subsidy,
            'amount' => 2000,
            'reference' => 'MDA-1-2026-0001',
        ]);

        $this->actingAs($user)
            ->get("/receipts/subsidy/{$transaction->id}")
            ->assertForbidden();
    }

    public function test_user_can_view_own_monthly_statement(): void
    {
        $user = User::factory()->create();
        $this->paidBooking(passenger: $user);
        $month = now()->format('Y-m');

        $this->actingAs($user)
            ->get("/receipts/statement/{$month}")
            ->assertOk()
            ->assertSee('MONTHLY COMMUTE STATEMENT')
            ->assertSee('Total commute cost')
            ->assertSee('600.00');
    }

    public function test_statement_rejects_invalid_month_format(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/receipts/statement/not-a-month')
            ->assertStatus(422);
    }

    public function test_public_verify_booking_reference(): void
    {
        $booking = $this->paidBooking();

        $this->get('/receipts/verify/booking/BK-'.$booking->id)
            ->assertOk()
            ->assertSee('Trip Booking Receipt')
            ->assertSee('BK-'.$booking->id)
            ->assertSee('Verified');
    }

    public function test_public_verify_rejects_unknown_reference(): void
    {
        $this->get('/receipts/verify/booking/BK-99999')->assertNotFound();
        $this->get('/receipts/verify/nonsense/BK-1')->assertNotFound();
    }

    public function test_public_verify_topup_reference(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);
        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::TopUp,
            'amount' => 5000,
            'reference' => 'TOPUP-WR-1-TEST',
            'tx_ref' => 'WR-1-TEST',
        ]);

        $this->get('/receipts/verify/topup/TOPUP-WR-1-TEST')
            ->assertOk()
            ->assertSee('Wallet Top-up Receipt')
            ->assertSee('5,000.00');
    }

    public function test_public_verify_statement_reference(): void
    {
        $user = User::factory()->create();
        $this->paidBooking(passenger: $user);
        $month = now()->format('Y-m');

        $this->get("/receipts/verify/statement/ST-{$user->id}-{$month}")
            ->assertOk()
            ->assertSee('Monthly Commute Statement')
            ->assertSee($user->name);
    }
}
