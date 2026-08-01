<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\GtfsStop;
use App\Models\Trip;
use App\Models\User;
use App\Services\GtfsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GtfsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function seedFeed(): void
    {
        GtfsStop::create([
            'stop_id' => 'KUB-01',
            'stop_name' => 'Kubwa Junction',
            'stop_lat' => 9.1117,
            'stop_lon' => 7.3304,
            'corridor' => Corridor::KubwaCbd->value,
        ]);
        GtfsStop::create([
            'stop_id' => 'KUB-16',
            'stop_name' => 'Federal Secretariat Gate',
            'stop_lat' => 9.0500,
            'stop_lon' => 7.4900,
            'corridor' => Corridor::KubwaCbd->value,
        ]);
        Trip::factory()->create(['corridor' => Corridor::KubwaCbd]);

        $this->app->make(GtfsService::class)->generate();
    }

    public function test_static_feed_returns_404_before_generation(): void
    {
        $this->get('/gtfs/gtfs.zip')->assertNotFound();
    }

    public function test_static_feed_downloads_after_generation(): void
    {
        $this->seedFeed();

        $this->get('/gtfs/gtfs.zip')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');
    }

    public function test_vehicle_positions_endpoint_is_public(): void
    {
        $this->get('/gtfs/gtfs-rt/vehicle_positions.pb')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/x-protobuf');
    }

    public function test_trip_updates_endpoint_is_public(): void
    {
        $this->get('/gtfs/gtfs-rt/trip_updates.pb')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/x-protobuf');
    }

    public function test_admin_gtfs_dashboard_requires_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/gtfs')->assertForbidden();
    }

    public function test_admin_can_view_gtfs_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/gtfs')
            ->assertOk()
            ->assertSee('GTFS Publisher')
            ->assertSee('Regenerate feed');
    }

    public function test_admin_can_regenerate_the_feed(): void
    {
        $this->seedFeed();

        $this->actingAs($this->admin())
            ->post('/admin/gtfs/regenerate')
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue(Storage::disk('public')->exists('gtfs/gtfs.zip'));
    }

    public function test_non_admin_cannot_regenerate_the_feed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/gtfs/regenerate')->assertForbidden();
    }
}
