<?php

namespace Tests\Feature;

use App\Enums\EmployerJoinVia;
use App\Enums\EmployerMemberStatus;
use App\Enums\EmployerProgramType;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Enums\VerificationLevel;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\EmployerWelcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployerEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.employer_programs.enabled' => true]);
    }

    private function employer(array $overrides = []): Employer
    {
        return Employer::create(array_merge([
            'name' => 'Enrollment Test Corp',
            'zone' => 'CBD',
            'program_type' => EmployerProgramType::Full,
            'corridors' => ['kubwa_cbd'],
            'active' => true,
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'phone' => '+23480'.mt_rand(10000000, 99999999),
            'verification_level' => VerificationLevel::Unverified,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_employers_page_requires_auth(): void
    {
        $this->get('/profile/employers')->assertRedirect('/login');
    }

    public function test_user_can_request_to_join_employer(): void
    {
        $employer = $this->employer();
        $user = $this->user();

        $this->actingAs($user)
            ->get('/profile/employers')
            ->assertOk()
            ->assertSee('Enrollment Test Corp');

        $this->actingAs($user)
            ->post("/employers/{$employer->id}/join")
            ->assertRedirect()
            ->assertSessionHas('status');

        $member = EmployerMember::where('employer_id', $employer->id)->where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($member->isPending());
        $this->assertSame(EmployerJoinVia::Self, $member->joined_via);
    }

    public function test_cannot_join_inactive_employer(): void
    {
        $employer = $this->employer(['active' => false]);
        $user = $this->user();

        $this->actingAs($user)
            ->post("/employers/{$employer->id}/join")
            ->assertSessionHasErrors('employer');

        $this->assertDatabaseMissing('employer_members', ['employer_id' => $employer->id, 'user_id' => $user->id]);
    }

    public function test_rejected_member_can_request_again(): void
    {
        $employer = $this->employer();
        $user = $this->user();
        $member = EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Rejected,
            'joined_via' => EmployerJoinVia::Employer,
        ]);

        $this->actingAs($user)
            ->post("/employers/{$employer->id}/join")
            ->assertRedirect();

        $this->assertTrue($member->fresh()->isPending());
        $this->assertSame(EmployerJoinVia::Self, $member->fresh()->joined_via);
    }

    public function test_suspended_member_cannot_rejoin(): void
    {
        $employer = $this->employer();
        $user = $this->user();
        EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Suspended,
            'joined_via' => EmployerJoinVia::Employer,
        ]);

        $this->actingAs($user)
            ->post("/employers/{$employer->id}/join")
            ->assertSessionHasErrors('employer');
    }

    public function test_approve_grants_level_one_and_phone_verified(): void
    {
        $employer = $this->employer();
        $user = $this->user();
        $member = EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Pending,
            'joined_via' => EmployerJoinVia::Self,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/admin/employer-members/{$member->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(EmployerMemberStatus::Active, $member->fresh()->status);
        $this->assertSame(VerificationLevel::WorkplaceVerified, $user->fresh()->verification_level);
        $this->assertTrue($user->fresh()->hasVerifiedPhone());
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'employer_member_approved']);
    }

    public function test_approve_never_downgrades_higher_level(): void
    {
        $employer = $this->employer();
        $user = $this->user(['verification_level' => VerificationLevel::NinVerified]);
        $member = EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Pending,
            'joined_via' => EmployerJoinVia::Self,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/employer-members/{$member->id}/approve")
            ->assertRedirect();

        $this->assertSame(VerificationLevel::NinVerified, $user->fresh()->verification_level);
    }

    public function test_cannot_approve_non_pending_member(): void
    {
        $employer = $this->employer();
        $user = $this->user();
        $member = EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Active,
            'joined_via' => EmployerJoinVia::Employer,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/employer-members/{$member->id}/approve")
            ->assertSessionHasErrors('member');
    }

    public function test_reject_and_review_return_member_to_queue(): void
    {
        $employer = $this->employer();
        $user = $this->user();
        $member = EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Pending,
            'joined_via' => EmployerJoinVia::Self,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/employer-members/{$member->id}/reject")
            ->assertRedirect();

        $this->assertSame(EmployerMemberStatus::Rejected, $member->fresh()->status);

        $this->actingAs($this->admin())
            ->put("/admin/employer-members/{$member->id}/review")
            ->assertRedirect();

        $this->assertTrue($member->fresh()->isPending());
    }

    public function test_admin_members_queue_renders(): void
    {
        $employer = $this->employer();
        $user = $this->user();
        EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Pending,
            'joined_via' => EmployerJoinVia::Self,
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/employers/{$employer->id}/members")
            ->assertOk()
            ->assertSee('Enrollment Test Corp');

        $this->actingAs($this->admin())
            ->get('/admin/employers/members/pending')
            ->assertOk();
    }

    public function test_header_labeled_csv_auto_creates_staff_account(): void
    {
        Notification::fake();

        $employer = $this->employer();

        $file = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents(
            $file,
            "email,name,phone,employee id\nnew.staff@example.ng,New Staff,+2348099991111,EMP-77\n"
        );

        $this->actingAs($this->admin())
            ->post("/admin/employers/{$employer->id}/enroll", ['csv' => new UploadedFile($file, 'enroll.csv', 'text/csv', null, true)])
            ->assertRedirect();

        @unlink($file);

        $staff = User::where('email', 'new.staff@example.ng')->firstOrFail();
        $this->assertSame('New Staff', $staff->name);
        $this->assertSame('+2348099991111', $staff->phone);
        $this->assertSame(VerificationLevel::WorkplaceVerified, $staff->verification_level);
        $this->assertTrue($staff->hasVerifiedPhone());

        $this->assertDatabaseHas('employer_members', [
            'employer_id' => $employer->id,
            'user_id' => $staff->id,
            'employee_id' => 'EMP-77',
            'status' => EmployerMemberStatus::Active->value,
            'joined_via' => EmployerJoinVia::Employer->value,
        ]);

        Notification::assertSentTo($staff, EmployerWelcome::class);
    }

    public function test_user_can_register_and_delete_own_vehicle(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get('/employer/vehicles')
            ->assertOk();

        $this->actingAs($user)
            ->post('/employer/vehicles', [
                'plate_number' => 'ABJ-777-KJ',
                'make' => 'Toyota',
                'model' => 'Hiace',
                'color' => 'White',
                'seats' => 14,
                'type' => VehicleType::Coaster->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $vehicle = Vehicle::where('plate_number', 'ABJ-777-KJ')->firstOrFail();
        $this->assertSame($user->id, $vehicle->user_id);

        $this->actingAs($user)
            ->delete("/employer/vehicles/{$vehicle->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_user_cannot_delete_someone_elses_vehicle(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->delete("/employer/vehicles/{$vehicle->id}")
            ->assertSessionHasErrors('vehicle');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }
}
