<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    private function passenger(): User
    {
        return User::factory()->create([
            'role' => UserRole::Passenger,
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);
    }

    public function test_guest_is_redirected_away_from_settings(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_or_update_settings(): void
    {
        $passenger = $this->passenger();

        $this->actingAs($passenger)->get(route('admin.settings.index'))->assertForbidden();

        $this->actingAs($passenger)
            ->post(route('admin.settings.store'), ['fares' => ['kubwa_cbd' => 900]])
            ->assertForbidden();
    }

    public function test_settings_page_renders_config_defaults_when_no_override(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Corridor fares')
            ->assertSee('Kubwa → CBD')
            ->assertSee('Nyanya → Idu')
            ->assertSee('Lugbe → CBD')
            ->assertSee('Default ₦800')
            ->assertSee('Default ₦700')
            ->assertSee('Default ₦600');
    }

    public function test_admin_can_override_a_corridor_fare_and_pricing_reads_it(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.store'), ['fares' => ['kubwa_cbd' => 950, 'nyanya_idu' => '', 'lugbe_cbd' => '']])
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('status');

        $this->assertSame('950', Setting::where('key', 'max_fare_per_corridor.kubwa_cbd')->value('value'));

        $this->assertSame(950.0, app(PricingService::class)->fareFor(Corridor::KubwaCbd));
        $this->assertSame(700.0, app(PricingService::class)->fareFor(Corridor::NyanyaIdu));
        $this->assertSame(600.0, app(PricingService::class)->fareFor(Corridor::LugbeCbd));

        $this->assertSame(
            1,
            ActivityLog::where('action', 'corridor_fare_updated')->where('user_id', $admin->id)->count()
        );
        $this->assertSame(
            ['corridor' => 'kubwa_cbd', 'from' => null, 'to' => 950],
            ActivityLog::where('action', 'corridor_fare_updated')->first()->changes
        );
    }

    public function test_blank_field_restores_the_config_default(): void
    {
        Setting::updateOrCreate(['key' => 'max_fare_per_corridor.lugbe_cbd'], ['value' => '900']);

        $this->actingAs($this->admin())
            ->post(route('admin.settings.store'), ['fares' => ['kubwa_cbd' => '', 'nyanya_idu' => '', 'lugbe_cbd' => '']]);

        $this->assertFalse(Setting::where('key', 'max_fare_per_corridor.lugbe_cbd')->exists());
        $this->assertSame(600.0, app(PricingService::class)->fareFor(Corridor::LugbeCbd));
    }

    public function test_fare_validation_accepts_only_reasonable_naira_ranges(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.store'), ['fares' => ['kubwa_cbd' => 50]])
            ->assertSessionHasErrors('fares.kubwa_cbd');

        $this->actingAs($this->admin())
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.store'), ['fares' => ['kubwa_cbd' => 99999]])
            ->assertSessionHasErrors('fares.kubwa_cbd');
    }
}
