<?php

namespace Tests\Feature;

use App\Enums\TrustLedgerType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\CommunityTrust;
use App\Models\User;
use App\Services\TrustService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    public function test_credit_is_idempotent_on_reference(): void
    {
        $service = app(TrustService::class);

        $first = $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');
        $second = $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, CommunityTrust::count());
        $this->assertSame(600.0, $service->balance(TrustLedgerType::TimeBankFloat));
    }

    public function test_debit_is_idempotent_on_reference(): void
    {
        $service = app(TrustService::class);
        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');

        $first = $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');
        $second = $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(2, CommunityTrust::count());
        $this->assertSame(0.0, $service->balance(TrustLedgerType::TimeBankFloat));
    }

    public function test_balance_is_net_and_per_type(): void
    {
        $service = app(TrustService::class);

        $service->credit(TrustLedgerType::TimeBankFloat, 1200, 'TB-FLOAT-1');
        $service->credit(TrustLedgerType::CommunitySubsidy, 5000, 'SUB-1');
        $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');
        $service->credit(TrustLedgerType::OperationsProfitShare, 1500, 'PS-1');

        $this->assertSame(600.0, $service->balance(TrustLedgerType::TimeBankFloat));
        $this->assertSame(5000.0, $service->balance(TrustLedgerType::CommunitySubsidy));
        $this->assertSame(1500.0, $service->balance(TrustLedgerType::OperationsProfitShare));
        $this->assertSame(7100.0, $service->balance());
    }

    public function test_running_balance_after_tracks_writes_per_type(): void
    {
        $service = app(TrustService::class);

        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');
        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-2');
        $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');
        $service->credit(TrustLedgerType::OperationsProfitShare, 1000, 'PS-1');

        $floats = CommunityTrust::where('type', TrustLedgerType::TimeBankFloat)
            ->orderBy('id')
            ->get();

        $this->assertSame('600.00', $floats[0]->balance_after);
        $this->assertSame('1200.00', $floats[1]->balance_after);
        $this->assertSame('600.00', $floats[2]->balance_after);
    }

    public function test_trust_report_requires_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/trust')
            ->assertForbidden();
    }

    public function test_admin_can_view_trust_report_with_kpis(): void
    {
        $service = app(TrustService::class);
        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');
        $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');
        $service->credit(TrustLedgerType::CommunitySubsidy, 5000, 'SUB-1');

        $this->actingAs($this->admin())
            ->get('/admin/trust')
            ->assertOk()
            ->assertSee('Community Trust')
            ->assertSee('Trust balance')
            ->assertSee('Float issued')
            ->assertSee('Float outstanding')
            ->assertSee('Ledger balanced')
            ->assertSee('TB-FLOAT-1')
            ->assertSee('TB-REPAY-1');
    }

    public function test_per_type_breakdown_renders_credits_debits_balance(): void
    {
        $service = app(TrustService::class);
        $service->credit(TrustLedgerType::TimeBankFloat, 1200, 'TB-FLOAT-1');
        $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');

        $this->actingAs($this->admin())
            ->get('/admin/trust')
            ->assertSee('Time-Bank float')
            ->assertSee('1,200.00')
            ->assertSee('600.00');
    }

    public function test_drifted_balance_after_is_flagged_for_review(): void
    {
        $service = app(TrustService::class);
        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');
        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-2');

        CommunityTrust::where('reference', 'TB-FLOAT-2')
            ->update(['balance_after' => 900]);

        $this->actingAs($this->admin())
            ->get('/admin/trust')
            ->assertSee('Reconciliation needs review')
            ->assertSee('TB-FLOAT-2');
    }

    public function test_trust_export_downloads_ledger_csv(): void
    {
        $service = app(TrustService::class);
        $service->credit(TrustLedgerType::TimeBankFloat, 600, 'TB-FLOAT-1');
        $service->debit(TrustLedgerType::TimeBankFloat, 600, 'TB-REPAY-1');

        $this->actingAs($this->admin())
            ->get('/admin/trust/export')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertSee('reference,type,direction,amount,balance_after,recorded_at,meta')
            ->assertSee('TB-FLOAT-1,time_bank_float,credit,600.00')
            ->assertSee('TB-REPAY-1,time_bank_float,debit,600.00');
    }

    public function test_trust_export_requires_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/trust/export')
            ->assertForbidden();
    }

    public function test_empty_ledger_shows_empty_state(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/trust')
            ->assertOk()
            ->assertSee('No Trust movements yet')
            ->assertSee('Ledger balanced')
            ->assertSee('0.00');
    }

    public function test_meta_is_round_tripped_into_json_on_export(): void
    {
        $service = app(TrustService::class);
        $service->credit(
            TrustLedgerType::TimeBankFloat,
            600,
            'TB-FLOAT-1',
            ['booking_id' => 42, 'seats' => 1],
        );

        $response = $this->actingAs($this->admin())
            ->get('/admin/trust/export');

        $response->assertOk();
        $rows = str_getcsv(trim($response->getContent()), "\n");
        $row = str_getcsv($rows[1]);

        $this->assertSame('TB-FLOAT-1', $row[0]);
        $this->assertSame('time_bank_float', $row[1]);
        $this->assertSame('credit', $row[2]);
        $this->assertSame('600.00', $row[3]);
        $this->assertSame(['booking_id' => 42, 'seats' => 1], json_decode($row[6], true));
    }
}
