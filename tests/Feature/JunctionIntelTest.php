<?php

namespace Tests\Feature;

use App\Enums\DemandDayType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\DemandSurvey;
use App\Models\Junction;
use App\Models\User;
use App\Services\DemandService;
use Database\Seeders\JunctionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JunctionIntelTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    // --- Seeder writes the demand-intel columns (gallery WORKRIDE-45-JUNCTIONS-SEED.sql) ---

    public function test_junction_seeder_writes_intel_columns(): void
    {
        $this->seed(JunctionSeeder::class);

        $this->assertGreaterThanOrEqual(40, Junction::count());

        $berger = Junction::where('name', 'Berger Junction')->firstOrFail();
        $this->assertSame(3500, (int) $berger->passenger_volume_daily);
        $this->assertTrue((bool) $berger->is_major_hub);
        $this->assertSame('FCT', $berger->state);
        $this->assertSame(20, (int) $berger->avg_wait_time_mins);

        $this->assertSame('Nasarawa', Junction::where('name', 'Mararaba Junction')->value('state'));
        $this->assertFalse((bool) Junction::where('name', 'Aco Estate Junction')->value('is_major_hub'));
    }

    // --- Navigation search: seeded volume fills the gap until surveys exist ---

    public function test_navigation_search_uses_seeded_volume_before_surveys_exist(): void
    {
        Http::fake(['*' => Http::response([])]);

        Junction::create([
            'name' => 'Kubwa Junction',
            'corridor' => 'kubwa_cbd',
            'lat' => 9.15,
            'lng' => 7.3333,
            'zone' => 'Kubwa',
            'is_active' => true,
            'passenger_volume_daily' => 2500,
        ]);

        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/navigation/search?q=Kubwa')
            ->assertOk()
            ->assertJsonPath('data.0.passenger_volume_daily', 2500);
    }

    public function test_navigation_search_survey_totals_win_over_seeded_volume(): void
    {
        Http::fake(['*' => Http::response([])]);

        $junction = Junction::create([
            'name' => 'Nyanya Under-Bridge',
            'corridor' => 'nyanya_idu',
            'lat' => 8.98,
            'lng' => 7.58,
            'zone' => 'Nyanya',
            'is_active' => true,
            'passenger_volume_daily' => 5000,
        ]);

        DemandSurvey::create([
            'junction_id' => $junction->id,
            'count' => 320,
            'destination_text' => 'CBD',
            'hour' => 7,
            'day_type' => DemandDayType::Weekday,
            'lat' => 8.98,
            'lng' => 7.58,
        ]);

        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/navigation/search?q=Nyanya')
            ->assertOk()
            ->assertJsonPath('data.0.passenger_volume_daily', 320);
    }

    // --- Control Tower demand page carries the intel ---

    public function test_junction_counts_carry_intel_keys(): void
    {
        Junction::create([
            'name' => 'Dei-Dei Junction',
            'corridor' => 'kubwa_cbd',
            'lat' => 9.11,
            'lng' => 7.28,
            'zone' => 'Dei-Dei',
            'is_active' => true,
            'passenger_volume_daily' => 1800,
            'is_major_hub' => true,
            'state' => 'FCT',
            'avg_wait_time_mins' => 30,
        ]);

        $counts = app(DemandService::class)->junctionCounts();
        $this->assertCount(1, $counts);

        $this->assertSame(1800, $counts[0]['passenger_volume_daily']);
        $this->assertTrue($counts[0]['is_major_hub']);
        $this->assertSame('FCT', $counts[0]['state']);
        $this->assertSame(30, $counts[0]['avg_wait_time_mins']);
    }

    public function test_admin_demand_page_renders_major_hub_badge(): void
    {
        Junction::create([
            'name' => 'Berger Junction',
            'corridor' => 'kubwa_cbd',
            'lat' => 9.082,
            'lng' => 7.445,
            'zone' => 'Wuse',
            'is_active' => true,
            'passenger_volume_daily' => 3500,
            'is_major_hub' => true,
            'avg_wait_time_mins' => 20,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/ops/demand')
            ->assertOk()
            ->assertSee('Demand Research')
            ->assertSee('Berger Junction')
            ->assertSee('Major hub');
    }
}
