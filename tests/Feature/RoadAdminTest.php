<?php

namespace Tests\Feature;

use App\Enums\RoadEventType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\RoadEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    public function test_admin_road_dashboard_requires_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/road')->assertForbidden();
    }

    public function test_admin_can_view_road_dashboard(): void
    {
        RoadEvent::factory()->count(3)->create(['is_confirmed' => true]);
        RoadEvent::factory()->count(2)->create(['is_confirmed' => false]);

        $this->actingAs($this->admin())
            ->get('/admin/road')
            ->assertOk()
            ->assertSee('Road Intelligence')
            ->assertSee('Export CSV for FERMA')
            ->assertSee('3');
    }

    public function test_ferma_csv_export_returns_confirmed_potholes(): void
    {
        RoadEvent::factory()->create([
            'is_confirmed' => true,
            'type' => RoadEventType::Pothole,
            'road_name' => 'Kubwa Expressway',
        ]);
        RoadEvent::factory()->create(['is_confirmed' => false]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/road/export');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertHeaderContains('Content-Disposition', 'attachment');

        $content = $response->getContent();

        $this->assertStringContainsString('road_name,lat,lng,type,severity,reported_at', $content);
        $this->assertStringContainsString('Kubwa Expressway', $content);
    }

    public function test_non_admin_cannot_export_csv(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/road/export')->assertForbidden();
    }
}
