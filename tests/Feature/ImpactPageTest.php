<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Models\ImpactStat;
use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpactPageTest extends TestCase
{
    use RefreshDatabase;

    private function rider(): User
    {
        return User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);
    }

    public function test_impact_page_requires_auth(): void
    {
        $this->get('/impact')->assertRedirect('/login');
    }

    public function test_impact_page_renders_personal_stats(): void
    {
        $user = $this->rider();
        ImpactStat::create([
            'user_id' => $user->id,
            'total_trips' => 10,
            'co2_saved_kg' => 26.40,
            'fuel_saved_litres' => 22.0,
            'trees_equivalent' => 1.26,
            'level' => 2,
        ]);

        $this->actingAs($user)
            ->get('/impact')
            ->assertOk()
            ->assertSee('How You Don Help Your Area')
            ->assertSee('26.4')
            ->assertSee('CO₂ certificate');
    }

    public function test_impact_page_shows_workplace_leaderboard(): void
    {
        $workplace = Workplace::factory()->create();

        $top = User::factory()->create(['workplace_id' => $workplace->id]);
        ImpactStat::create(['user_id' => $top->id, 'co2_saved_kg' => 50, 'total_trips' => 5, 'fuel_saved_litres' => 10, 'trees_equivalent' => 2, 'level' => 1]);

        $me = $this->rider();
        $me->update(['workplace_id' => $workplace->id]);
        ImpactStat::create(['user_id' => $me->id, 'co2_saved_kg' => 10, 'total_trips' => 2, 'fuel_saved_litres' => 4, 'trees_equivalent' => 0.5, 'level' => 1]);

        $this->actingAs($me)
            ->get('/impact')
            ->assertOk()
            ->assertSee('Workplace leaderboard');
    }

    public function test_certificate_requires_auth(): void
    {
        $this->get('/impact/certificate/co2')->assertRedirect('/login');
    }

    public function test_co2_certificate_renders_with_qr(): void
    {
        $user = $this->rider();
        ImpactStat::create([
            'user_id' => $user->id,
            'total_trips' => 20,
            'co2_saved_kg' => 52.80,
            'fuel_saved_litres' => 44.0,
            'trees_equivalent' => 2.51,
            'level' => 3,
        ]);

        $this->actingAs($user)
            ->get('/impact/certificate/co2')
            ->assertOk()
            ->assertSee('CO₂ SAVED CERTIFICATE')
            ->assertSee('52.8')
            ->assertSee('data:image/svg+xml;base64');
    }

    public function test_fuel_certificate_renders(): void
    {
        $user = $this->rider();
        ImpactStat::create([
            'user_id' => $user->id,
            'total_trips' => 5,
            'co2_saved_kg' => 10,
            'fuel_saved_litres' => 8.8,
            'trees_equivalent' => 0.5,
            'level' => 2,
        ]);

        $this->actingAs($user)
            ->get('/impact/certificate/fuel')
            ->assertOk()
            ->assertSee('FUEL SAVED CERTIFICATE')
            ->assertSee('8.8');
    }

    public function test_invalid_certificate_type_is_404(): void
    {
        $this->actingAs($this->rider())
            ->get('/impact/certificate/bogus')
            ->assertNotFound();
    }

    public function test_public_verify_page_confirms_co2(): void
    {
        $user = $this->rider();
        ImpactStat::create([
            'user_id' => $user->id,
            'total_trips' => 5,
            'co2_saved_kg' => 10,
            'fuel_saved_litres' => 8.8,
            'trees_equivalent' => 0.5,
            'level' => 2,
        ]);

        $this->get("/impact/verify/{$user->id}/co2")
            ->assertOk()
            ->assertSee('Verified — authentic WorkRide record');
    }

    public function test_public_verify_page_shows_no_savings_when_empty(): void
    {
        $user = $this->rider();

        $this->get("/impact/verify/{$user->id}/fuel")
            ->assertOk()
            ->assertSee('no fuel savings yet');
    }
}
