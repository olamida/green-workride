<?php

namespace Tests\Feature;

use App\Enums\DemandDayType;
use App\Enums\VerificationLevel;
use App\Models\DemandSurvey;
use App\Models\Junction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandHotspotsTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ], $overrides));
    }

    private function junction(): Junction
    {
        return Junction::create([
            'name' => 'Berger Junction',
            'corridor' => 'nyanya_idu',
            'lat' => 9.03,
            'lng' => 7.433,
            'zone' => 'FCT-Keffi corridor',
            'is_active' => true,
        ]);
    }

    private function countAt(Junction $junction, int $count = 14): void
    {
        DemandSurvey::create([
            'junction_id' => $junction->id,
            'count' => $count,
            'destination_text' => 'CBD',
            'hour' => 7,
            'day_type' => DemandDayType::Weekday,
            'lat' => $junction->lat,
            'lng' => $junction->lng,
        ]);
    }

    // --- Board "How to book" strip (hotspot branch) ---

    public function test_board_strip_shows_live_hotspot_when_no_next_departure(): void
    {
        $junction = $this->junction();
        $this->countAt($junction, 14);

        $this->actingAs($this->user())
            ->get('/trips')
            ->assertOk()
            ->assertSee('Live demand at')
            ->assertSee('Berger Junction');
    }

    public function test_board_strip_be_the_driver_link_is_gated_to_volunteer_drivers(): void
    {
        $junction = $this->junction();
        $this->countAt($junction, 14);

        $driver = $this->user(['verification_level' => 1]);
        $response = $this->actingAs($driver)->get('/trips');
        $response->assertOk();
        $this->assertStringContainsString('Publish motor', $response->getContent());

        $phoneUser = $this->user(['phone_verified_at' => now()]);
        $response2 = $this->actingAs($phoneUser)->get('/trips');
        $response2->assertOk();
        $this->assertStringContainsString('Live demand at', $response2->getContent());
        $this->assertStringNotContainsString('Publish motor', $response2->getContent());
        $this->assertStringContainsString('we\'re matching a driver', $response2->getContent());
    }

    // --- Board empty state (hotspot list + Be the driver CTA) ---

    public function test_board_empty_state_lists_hotspots_with_be_the_driver_cta_for_drivers(): void
    {
        $junction = $this->junction();
        $this->countAt($junction, 14);

        $this->actingAs($this->user(['verification_level' => 1]))
            ->get('/trips')
            ->assertOk()
            ->assertSee('14 people waiting')
            ->assertSee('Publish motor')
            ->assertSee(route('trips.create', ['corridor' => 'nyanya_idu']));
    }

    public function test_board_empty_state_hides_be_the_driver_cta_for_phone_only_users(): void
    {
        $junction = $this->junction();
        $this->countAt($junction, 14);

        $this->actingAs($this->user(['phone_verified_at' => now()]))
            ->get('/trips')
            ->assertOk()
            ->assertSee('14 people waiting')
            ->assertDontSee('Be the driver');
    }

    // --- /go (navigation home) empty state ---

    public function test_go_page_shows_hotspots_in_empty_state(): void
    {
        $junction = $this->junction();
        $this->countAt($junction, 14);

        $this->actingAs($this->user())
            ->get('/go')
            ->assertOk()
            ->assertSee('Berger Junction')
            ->assertSee('14 people waiting');
    }

    // --- trips.create corridor prefill ---

    public function test_trips_create_prefills_corridor_from_hotspot_query(): void
    {
        $this->actingAs($this->user(['verification_level' => 1]))
            ->get('/trips/create?corridor=nyanya_idu')
            ->assertOk()
            ->assertSee("corridor: 'nyanya_idu'", false);
    }

    public function test_trips_create_defaults_to_kubwa_cbd_without_corridor_query(): void
    {
        $this->actingAs($this->user(['verification_level' => 1]))
            ->get('/trips/create')
            ->assertOk()
            ->assertSee("corridor: 'kubwa_cbd'", false);
    }

    public function test_trips_create_ignores_invalid_corridor_query(): void
    {
        $this->actingAs($this->user(['verification_level' => 1]))
            ->get('/trips/create?corridor=nowhere')
            ->assertOk()
            ->assertSee("corridor: 'kubwa_cbd'", false);
    }
}
