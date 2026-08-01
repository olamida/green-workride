<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    public function test_non_admin_cannot_access_control_tower(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_guest_cannot_access_control_tower(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Control Tower')
            ->assertSee('Users');
    }

    public function test_admin_can_view_and_filter_verifications(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $verification = $user->verifications()->create([
            'type' => 'workplace_id',
            'workplace_id' => Workplace::factory()->create()->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get('/admin/verifications')
            ->assertOk()
            ->assertSee($user->name);

        $this->actingAs($admin)
            ->get('/admin/verifications?status=pending')
            ->assertOk()
            ->assertSee($user->name);

        $this->actingAs($admin)
            ->get('/admin/verifications?status=approved')
            ->assertOk()
            ->assertDontSee($user->name);
    }

    public function test_admin_can_approve_workplace_verification_and_level_updates(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['verification_level' => VerificationLevel::Unverified]);
        $verification = $user->verifications()->create([
            'type' => 'workplace_id',
            'workplace_id' => Workplace::factory()->create()->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/verifications/{$verification->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('verifications', [
            'id' => $verification->id,
            'status' => 'approved',
            'verified_by' => $admin->id,
        ]);

        $this->assertEquals(VerificationLevel::WorkplaceVerified, $user->fresh()->verification_level);
    }

    public function test_admin_can_approve_nin_verification_to_level_two(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['verification_level' => VerificationLevel::WorkplaceVerified]);
        $verification = $user->verifications()->create([
            'type' => 'nin',
            'nin_last4' => '1234',
            'document_hash' => str_repeat('a', 64),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/verifications/{$verification->id}/approve")
            ->assertRedirect();

        $this->assertEquals(VerificationLevel::NinVerified, $user->fresh()->verification_level);
    }

    public function test_admin_can_reject_verification_with_note(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['verification_level' => VerificationLevel::Unverified]);
        $verification = $user->verifications()->create([
            'type' => 'workplace_id',
            'workplace_id' => Workplace::factory()->create()->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/verifications/{$verification->id}/reject", [
                'note' => 'Document unreadable.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('verifications', [
            'id' => $verification->id,
            'status' => 'rejected',
            'admin_note' => 'Document unreadable.',
        ]);

        $this->assertEquals(VerificationLevel::Unverified, $user->fresh()->verification_level);
    }

    public function test_rejection_requires_a_note(): void
    {
        $admin = $this->admin();
        $verification = User::factory()->create()->verifications()->create([
            'type' => 'nin',
            'nin_last4' => '1234',
            'document_hash' => str_repeat('a', 64),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post("/admin/verifications/{$verification->id}/reject", ['note' => ''])
            ->assertSessionHasErrors('note');
    }

    public function test_admin_can_list_search_and_filter_users(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['name' => 'Chidi Nwosu', 'verification_level' => VerificationLevel::NinVerified]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Chidi Nwosu');

        $this->actingAs($admin)
            ->get('/admin/users?search=Chidi')
            ->assertOk()
            ->assertSee('Chidi Nwosu');

        $this->actingAs($admin)
            ->get('/admin/users?level=2')
            ->assertOk()
            ->assertSee('Chidi Nwosu');
    }

    public function test_admin_can_ban_and_unban_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/ban")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->is_banned);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/unban")
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_banned);
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin)
            ->post("/admin/users/{$otherAdmin->id}/ban")
            ->assertForbidden();

        $this->assertFalse($otherAdmin->fresh()->is_banned);
    }

    public function test_admin_can_view_workplaces(): void
    {
        $admin = $this->admin();
        $workplace = Workplace::factory()->create(['name' => 'Federal Ministry of Works']);

        $this->actingAs($admin)
            ->get('/admin/workplaces')
            ->assertOk()
            ->assertSee('Federal Ministry of Works');
    }
}
