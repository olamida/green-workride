<?php

namespace Tests\Feature;

use App\Enums\RoadEventType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\RoadEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadSensorTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function eventPayload(array $overrides = []): array
    {
        return array_merge([
            'lat' => 9.0515,
            'lng' => 7.4955,
            'type' => 'pothole',
            'severity' => 3,
            'speed' => 40.5,
            'accelerometer_z' => 18.2,
            'road_name' => 'Kubwa Expressway',
        ], $overrides);
    }

    public function test_unauthenticated_user_cannot_report_a_road_event(): void
    {
        $this->postJson('/api/v1/road-events', $this->eventPayload())
            ->assertUnauthorized();
    }

    public function test_verified_driver_can_report_a_road_event(): void
    {
        $user = $this->verifiedUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/road-events', $this->eventPayload());

        $response->assertCreated()
            ->assertJsonPath('event.type', 'pothole')
            ->assertJsonPath('event.severity', 3)
            ->assertJsonPath('event.is_confirmed', false);

        $this->assertDatabaseHas('road_events', [
            'user_id' => $user->id,
            'type' => 'pothole',
            'road_name' => 'Kubwa Expressway',
        ]);
    }

    public function test_event_outside_fct_is_rejected(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/road-events', $this->eventPayload([
                'lat' => 6.5244,
                'lng' => 3.3792,
            ]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Road events can only be collected inside the FCT.');
    }

    public function test_validation_requires_a_valid_type(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/road-events', $this->eventPayload(['type' => 'crater']))
            ->assertStatus(422);
    }

    public function test_public_road_events_endpoint_returns_only_confirmed_potholes(): void
    {
        RoadEvent::factory()->count(2)->create(['is_confirmed' => true, 'type' => RoadEventType::Pothole]);
        RoadEvent::factory()->count(1)->create(['is_confirmed' => false, 'type' => RoadEventType::Pothole]);

        $response = $this->getJson('/api/v1/road-events');

        $response->assertOk();
        $events = $response->json('events');

        $this->assertCount(2, $events);
        $this->assertArrayNotHasKey('user_id', $events[0]);
    }

    public function test_public_road_map_page_renders(): void
    {
        RoadEvent::factory()->create(['is_confirmed' => true, 'type' => RoadEventType::Pothole]);

        $this->get('/road/map')
            ->assertOk()
            ->assertSee('Road Intelligence')
            ->assertSee('road-map');
    }
}
