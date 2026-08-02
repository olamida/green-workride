<?php

namespace Tests\Feature;

use App\Enums\MissionActivityType;
use App\Enums\MissionStatus;
use App\Enums\MissionSubmissionStatus;
use App\Enums\MissionVerificationMode;
use App\Enums\RewardType;
use App\Enums\TripStatus as TripStatusEnum;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Mission;
use App\Models\MissionProgress;
use App\Models\RoadEvent;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MissionService;
use App\Services\RoadIntelligenceService;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.missions.enabled' => true]);
    }

    private function user(string $role = 'passenger', VerificationLevel $level = VerificationLevel::WorkplaceVerified): User
    {
        $user = User::factory()->create([
            'role' => UserRole::from($role),
            'verification_level' => $level,
        ]);

        Wallet::create(['user_id' => $user->id, 'cash_balance' => 1000]);

        return $user;
    }

    private function mission(array $overrides = []): Mission
    {
        return Mission::create(array_merge([
            'name' => 'Give free rides',
            'slug' => 'give-free-rides-'.strtolower(Str::random(6)),
            'sponsor_type' => 'community',
            'activity_type' => MissionActivityType::VolunteerRides,
            'metric_goal' => 2,
            'metric_window_days' => 7,
            'reward_type' => RewardType::Cash,
            'reward_value' => 500,
            'verification_mode' => MissionVerificationMode::Auto,
            'status' => MissionStatus::Published,
        ], $overrides));
    }

    public function test_record_ignored_when_feature_disabled(): void
    {
        config(['workride.missions.enabled' => false]);

        $mission = $this->mission();
        $user = $this->user('volunteer');

        $awarded = app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);

        $this->assertEmpty($awarded);
        $this->assertDatabaseMissing('mission_progress', ['mission_id' => $mission->id]);
    }

    public function test_record_counts_toward_matching_mission(): void
    {
        $mission = $this->mission(['metric_goal' => 5]);
        $user = $this->user('volunteer');

        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);
        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);
        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);

        $progress = MissionProgress::where('user_id', $user->id)->where('mission_id', $mission->id)->first();

        $this->assertEquals(3, $progress->metric_count);
        $this->assertEquals('in_progress', $progress->status->value);
        $this->assertEquals(1000.0, (float) $user->fresh()->wallet->cash_balance);
    }

    public function test_auto_mission_awards_cash_on_goal_reached(): void
    {
        $mission = $this->mission(['metric_goal' => 2]);
        $user = $this->user('volunteer');

        $awarded = app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);
        $this->assertEmpty($awarded);

        $awarded = app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);

        $this->assertCount(1, $awarded);
        $this->assertEquals(1500.0, (float) $user->fresh()->wallet->cash_balance);

        $progress = MissionProgress::where('user_id', $user->id)->where('mission_id', $mission->id)->first();
        $this->assertEquals('awarded', $progress->status->value);
        $this->assertEquals(2, $progress->metric_count);
        $this->assertStringStartsWith('MIS-', $progress->reference);

        $this->assertEquals(500.0, (float) $mission->fresh()->budget_spent);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mission_awarded',
            'model_type' => Mission::class,
        ]);
    }

    public function test_auto_mission_never_double_awards(): void
    {
        $mission = $this->mission(['metric_goal' => 1]);
        $user = $this->user('volunteer');

        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);
        $second = app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);

        $this->assertEmpty($second);
        $this->assertEquals(1500.0, (float) $user->fresh()->wallet->cash_balance);
    }

    public function test_ignores_non_matching_activity(): void
    {
        $mission = $this->mission();
        $user = $this->user();

        app(MissionService::class)->record($user, MissionActivityType::PaidRides);

        $this->assertDatabaseMissing('mission_progress', ['mission_id' => $mission->id]);
    }

    public function test_ignores_unpublished_and_ended_missions(): void
    {
        $user = $this->user('volunteer');

        $draft = $this->mission(['name' => 'Draft', 'status' => MissionStatus::Draft]);
        $ended = $this->mission(['name' => 'Ended', 'ends_at' => now()->subDay()]);

        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);

        $this->assertDatabaseMissing('mission_progress', ['mission_id' => $draft->id]);
        $this->assertDatabaseMissing('mission_progress', ['mission_id' => $ended->id]);
    }

    public function test_budget_exhaustion_stops_award_for_new_members(): void
    {
        $mission = $this->mission(['metric_goal' => 1, 'reward_value' => 500, 'budget_total' => 500]);
        $first = $this->user('volunteer');
        $second = $this->user('volunteer');

        $firstAwarded = app(MissionService::class)->record($first, MissionActivityType::VolunteerRides);

        $this->assertCount(1, $firstAwarded);
        $this->assertEquals(500.0, (float) $mission->fresh()->budget_spent);

        $secondAwarded = app(MissionService::class)->record($second, MissionActivityType::VolunteerRides);

        $this->assertEmpty($secondAwarded);
        $this->assertEquals(1000.0, (float) $second->fresh()->wallet->cash_balance);
        $this->assertDatabaseMissing('mission_progress', ['mission_id' => $mission->id, 'user_id' => $second->id]);
    }

    public function test_green_points_and_earned_rewards_credit_correctly(): void
    {
        $user = $this->user('volunteer');

        $gp = $this->mission(['name' => 'GP', 'metric_goal' => 1, 'reward_type' => RewardType::GreenPoints, 'reward_value' => 100]);
        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);
        $this->assertEquals(100, (int) $user->fresh()->green_points);

        $earned = $this->mission(['name' => 'Earned', 'metric_goal' => 1, 'reward_type' => RewardType::Earned, 'reward_value' => 300]);
        app(MissionService::class)->record($user, MissionActivityType::VolunteerRides);
        $this->assertEquals(300.0, (float) $user->fresh()->wallet->earned_balance);
    }

    public function test_submit_proof_creates_pending_submission(): void
    {
        Storage::fake('public');

        $mission = $this->mission(['verification_mode' => MissionVerificationMode::Proof]);
        $user = $this->user();

        $submission = app(MissionService::class)->submitProof($user, $mission, [
            'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
            'note' => 'At the site',
        ]);

        $this->assertDatabaseHas('mission_submissions', [
            'id' => $submission->id,
            'status' => MissionSubmissionStatus::Pending->value,
            'note' => 'At the site',
        ]);
        Storage::disk('public')->assertExists($submission->proof_photo_path);
    }

    public function test_submit_proof_blocked_when_disabled(): void
    {
        config(['workride.missions.enabled' => false]);

        $mission = $this->mission(['verification_mode' => MissionVerificationMode::Proof]);
        $user = $this->user();

        $this->expectException(ValidationException::class);

        app(MissionService::class)->submitProof($user, $mission, ['proof_photo' => UploadedFile::fake()->image('proof.jpg')]);
    }

    public function test_review_reject_does_not_pay(): void
    {
        Storage::fake('public');

        $mission = $this->mission(['verification_mode' => MissionVerificationMode::Proof]);
        $user = $this->user();
        $reviewer = $this->user('passenger', VerificationLevel::Unverified);

        $submission = app(MissionService::class)->submitProof($user, $mission, [
            'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        app(MissionService::class)->review($reviewer, $submission, false);

        $this->assertEquals(MissionSubmissionStatus::Rejected, $submission->fresh()->status);
        $this->assertEquals(1000.0, (float) $user->fresh()->wallet->cash_balance);
    }

    public function test_review_approve_pays_reward_exactly_once(): void
    {
        Storage::fake('public');

        $mission = $this->mission(['verification_mode' => MissionVerificationMode::Proof]);
        $user = $this->user();
        $reviewer = $this->user('passenger', VerificationLevel::Unverified);

        $submission = app(MissionService::class)->submitProof($user, $mission, [
            'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        app(MissionService::class)->review($reviewer, $submission, true);
        app(MissionService::class)->review($reviewer, $submission, true);

        $fresh = $submission->fresh();
        $this->assertEquals(MissionSubmissionStatus::Approved, $fresh->status);
        $this->assertTrue($fresh->reward_awarded);
        $this->assertEquals(1500.0, (float) $user->fresh()->wallet->cash_balance);
        $this->assertEquals(500.0, (float) $mission->fresh()->budget_spent);
    }

    public function test_web_rider_hub_renders_live_missions(): void
    {
        $this->mission(['name' => 'Kubwa free rides']);
        $user = $this->user('volunteer');

        $this->actingAs($user)
            ->get('/missions')
            ->assertOk()
            ->assertSee('Kubwa free rides')
            ->assertSee('500 cash');
    }

    public function test_web_proof_submission_route(): void
    {
        Storage::fake('public');

        $mission = $this->mission(['verification_mode' => MissionVerificationMode::Proof]);
        $user = $this->user();

        $this->actingAs($user)
            ->post("/missions/{$mission->id}/proof", [
                'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
                'note' => 'Done',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('mission_submissions', [
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'status' => MissionSubmissionStatus::Pending->value,
        ]);
    }

    public function test_admin_index_requires_admin(): void
    {
        $this->actingAs($this->user())->get('/admin/missions')->assertForbidden();
    }

    public function test_admin_can_create_and_toggle_mission(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'verification_level' => VerificationLevel::Unverified]);

        $this->actingAs($admin)
            ->post('/admin/missions', [
                'name' => 'Peak hour rides',
                'activity_type' => MissionActivityType::PeakHourRides->value,
                'verification_mode' => MissionVerificationMode::Auto->value,
                'metric_goal' => 10,
                'metric_window_days' => 7,
                'reward_type' => RewardType::Earned->value,
                'reward_value' => 1000,
                'sponsor_type' => 'private',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.missions.index'));

        $mission = Mission::where('name', 'Peak hour rides')->firstOrFail();
        $this->assertEquals(MissionStatus::Published, $mission->status);

        $this->actingAs($admin)
            ->post("/admin/missions/{$mission->id}/toggle")
            ->assertRedirect();

        $this->assertEquals(MissionStatus::Draft, $mission->fresh()->status);
    }

    public function test_admin_can_review_submission_via_web(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::Admin, 'verification_level' => VerificationLevel::Unverified]);
        $mission = $this->mission(['verification_mode' => MissionVerificationMode::Proof]);
        $user = $this->user();

        $submission = app(MissionService::class)->submitProof($user, $mission, [
            'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $this->actingAs($admin)
            ->post("/admin/missions/submissions/{$submission->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(MissionSubmissionStatus::Approved, $submission->fresh()->status);
        $this->assertEquals(1500.0, (float) $user->fresh()->wallet->cash_balance);
    }

    public function test_trip_completion_counts_volunteer_mission(): void
    {
        $mission = $this->mission(['metric_goal' => 1]);
        $driver = $this->user('volunteer');

        $trip = Trip::factory()->forDriver($driver)->volunteer()->create([
            'status' => TripStatusEnum::Active,
        ]);

        app(TripService::class)->completeTrip($trip, $driver);

        $this->assertEquals('awarded', MissionProgress::where('user_id', $driver->id)->where('mission_id', $mission->id)->firstOrFail()->status->value);
    }

    public function test_pothole_events_count_toward_road_missions(): void
    {
        $reports = $this->mission(['name' => 'Reports', 'activity_type' => MissionActivityType::PotholeReports, 'metric_goal' => 2]);
        $confirmed = $this->mission(['name' => 'Confirmed', 'activity_type' => MissionActivityType::PotholesConfirmed, 'metric_goal' => 1]);
        $user = $this->user('passenger');

        config([
            'workride.pothole_confirm.radius_m' => 20,
            'workride.pothole_confirm.within_hours' => 72,
            'workride.pothole_confirm.min_reports' => 2,
        ]);

        for ($i = 0; $i < 2; $i++) {
            app(RoadIntelligenceService::class)->recordEvent([
                'user_id' => $user->id,
                'lat' => 9.05 + ($i * 0.00001),
                'lng' => 7.45,
                'type' => 'pothole',
                'severity' => 3,
                'accelerometer_z' => 20,
            ]);
        }

        $this->assertEquals('awarded', MissionProgress::where('user_id', $user->id)->where('mission_id', $reports->id)->firstOrFail()->status->value);
        $this->assertEquals('awarded', MissionProgress::where('user_id', $user->id)->where('mission_id', $confirmed->id)->firstOrFail()->status->value);
        $this->assertEquals(2, RoadEvent::count());
    }
}
