<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Services\RoleSwitcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationFirstTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    public function test_admin_dashboard_renders_grouped_nav(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Demand Research')
            ->assertSee('Intelligence')
            ->assertSee('Road Intelligence')
            ->assertSee('Community Trust')
            ->assertSee('View as:');
    }

    public function test_admin_can_switch_view_as_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/admin')
            ->post('/admin/view-as', ['role' => 'passenger'])
            ->assertRedirect('/admin');

        $this->assertSame('passenger', session('view_as_role'));
        $this->assertSame(UserRole::Passenger, app(RoleSwitcherService::class)->effectiveRole($admin));
        $this->assertTrue(app(RoleSwitcherService::class)->isViewingAs($admin));

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSee('Viewing as')
            ->assertSee('Passenger');
    }

    public function test_admin_can_reset_view_as(): void
    {
        $admin = $this->admin();
        session(['view_as_role' => 'driver']);

        $this->actingAs($admin)
            ->from('/admin')
            ->post('/admin/view-as/reset')
            ->assertRedirect('/admin');

        $this->assertNull(session('view_as_role'));
        $this->assertFalse(app(RoleSwitcherService::class)->isViewingAs($admin));
        $this->assertSame(UserRole::Admin, app(RoleSwitcherService::class)->effectiveRole($admin));
    }

    public function test_view_as_switch_is_display_only_never_changes_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/view-as', ['role' => 'driver']);

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_non_admin_cannot_switch_view_as(): void
    {
        $user = User::factory()->create(['role' => UserRole::Passenger]);

        $this->actingAs($user)
            ->post('/admin/view-as', ['role' => 'passenger'])
            ->assertForbidden();
    }

    public function test_invalid_view_as_role_resets_to_admin(): void
    {
        $admin = $this->admin();
        session(['view_as_role' => 'driver']);

        $this->actingAs($admin)->post('/admin/view-as', ['role' => 'admin']);

        $this->assertNull(session('view_as_role'));
    }

    public function test_effective_role_ignores_switch_for_non_admins(): void
    {
        $passenger = User::factory()->create(['role' => UserRole::Passenger]);
        session(['view_as_role' => 'driver']);

        $this->assertSame(
            UserRole::Passenger,
            app(RoleSwitcherService::class)->effectiveRole($passenger)
        );
    }
}
