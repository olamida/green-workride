<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function paidBooking(float $fare = 600): Booking
    {
        return Booking::factory()->create([
            'status' => BookingStatus::Completed,
            'fare_paid' => $fare,
            'payment_method' => PaymentMethod::Wallet,
        ]);
    }

    public function test_business_dashboard_requires_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/business')
            ->assertForbidden();
    }

    public function test_admin_can_view_business_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/business')
            ->assertOk()
            ->assertSee('Business Dashboard')
            ->assertSee('Gross revenue')
            ->assertSee('MRR')
            ->assertSee('Subsidy utilization')
            ->assertSee('Transactions Excel');
    }

    public function test_gross_revenue_reflects_completed_paid_bookings(): void
    {
        $this->paidBooking(600);
        $this->paidBooking(700);
        $this->paidBooking(0); // free / volunteer ride — excluded

        $this->actingAs($this->admin())
            ->get('/admin/business')
            ->assertSee('1,300.00');
    }

    public function test_earnings_and_subsidy_kpis_are_aggregated(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Earned,
            'amount' => 420.50,
            'reference' => 'EARN-1',
        ]);
        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Subsidy,
            'amount' => 1000,
            'reference' => 'MDA-1-2026-0001',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/business')
            ->assertSee('420.50')
            ->assertSee('1,000.00');
    }

    public function test_export_transactions_returns_excel(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);
        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Subsidy,
            'amount' => 500,
            'reference' => 'MDA-1-2026-0001',
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/business/export/transactions');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_export_subsidy_utilization_returns_excel(): void
    {
        $workplace = Workplace::factory()->create(['name' => 'Federal Ministry of Finance']);
        $user = User::factory()->create(['workplace_id' => $workplace->id]);
        $wallet = Wallet::create(['user_id' => $user->id]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Subsidy,
            'amount' => 2000,
            'reference' => 'MDA-1-2026-0001',
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/business/export/subsidy');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_export_settlements_returns_driver_totals(): void
    {
        $driver = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $driver->id]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Earned,
            'amount' => 420,
            'reference' => 'EARN-1',
            'meta' => ['fare' => 600],
        ]);

        $response = $this->actingAs($this->admin())->get('/admin/business/export/settlements');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_non_admin_cannot_export(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/business/export/transactions')
            ->assertForbidden();
    }

    public function test_payouts_kpi_totals_payout_ledger(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id]);
        Payout::create([
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'completed',
            'reference' => 'PO-1-2026',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/business')
            ->assertSee('5,000.00');
    }
}
