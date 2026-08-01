<?php

namespace Tests\Feature;

use App\Enums\RewardAudience;
use App\Enums\RewardPeriod;
use App\Enums\RewardTrigger;
use App\Enums\RewardType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\RewardCampaign;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.rewards.enabled' => true]);
    }

    private function user(string $role = 'passenger'): User
    {
        $user = User::factory()->create([
            'role' => UserRole::from($role),
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id, 'cash_balance' => 1000]);

        return $user;
    }

    private function campaign(array $overrides = []): RewardCampaign
    {
        return RewardCampaign::create(array_merge([
            'name' => 'Test campaign',
            'audience' => RewardAudience::Both,
            'trigger' => RewardTrigger::TripCompleted,
            'reward_type' => RewardType::Cash,
            'reward_value' => 500,
            'period' => RewardPeriod::Once,
            'active' => true,
        ], $overrides));
    }

    public function test_rewards_disabled_no_award(): void
    {
        config(['workride.rewards.enabled' => false]);

        $campaign = $this->campaign();
        $user = $this->user();

        $awarded = app(RewardService::class)->award($user, RewardTrigger::TripCompleted);

        $this->assertEmpty($awarded);
        $this->assertDatabaseMissing('reward_claims', ['campaign_id' => $campaign->id]);
    }

    public function test_cash_campaign_awards_wallet_cash(): void
    {
        $campaign = $this->campaign();
        $user = $this->user();

        $awarded = app(RewardService::class)->award($user, RewardTrigger::TripCompleted);

        $this->assertCount(1, $awarded);
        $this->assertEquals(1500.0, (float) $user->fresh()->wallet->cash_balance);
        $this->assertDatabaseHas('reward_claims', [
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'reward_type' => RewardType::Cash->value,
        ]);
    }

    public function test_once_campaign_awards_only_once(): void
    {
        $this->campaign(['period' => RewardPeriod::Once]);
        $user = $this->user();

        app(RewardService::class)->award($user, RewardTrigger::TripCompleted);
        app(RewardService::class)->award($user, RewardTrigger::TripCompleted);

        $this->assertEquals(1, RewardClaim::where('user_id', $user->id)->count());
    }

    public function test_weekly_campaign_awards_once_per_week(): void
    {
        $this->campaign(['period' => RewardPeriod::Weekly]);
        $user = $this->user();

        app(RewardService::class)->award($user, RewardTrigger::TripCompleted);
        app(RewardService::class)->award($user, RewardTrigger::TripCompleted);

        $this->assertEquals(1, RewardClaim::where('user_id', $user->id)->count());
        $this->assertEquals(1500.0, (float) $user->fresh()->wallet->cash_balance);
    }

    public function test_audience_volunteers_excludes_passengers(): void
    {
        $this->campaign([
            'audience' => RewardAudience::Volunteers,
            'trigger' => RewardTrigger::VolunteerRide,
            'reward_type' => RewardType::GreenPoints,
            'reward_value' => 10,
            'period' => RewardPeriod::Unlimited,
        ]);
        $passenger = $this->user();

        $awarded = app(RewardService::class)->award($passenger, RewardTrigger::VolunteerRide);

        $this->assertEmpty($awarded);
        $this->assertEquals(0, (int) $passenger->fresh()->green_points);
    }

    public function test_volunteer_ride_awards_green_points(): void
    {
        $this->campaign([
            'audience' => RewardAudience::Volunteers,
            'trigger' => RewardTrigger::VolunteerRide,
            'reward_type' => RewardType::GreenPoints,
            'reward_value' => 10,
            'period' => RewardPeriod::Unlimited,
        ]);
        $volunteer = $this->user('volunteer');

        app(RewardService::class)->award($volunteer, RewardTrigger::VolunteerRide, ['event_key' => 'trip-1']);
        app(RewardService::class)->award($volunteer, RewardTrigger::VolunteerRide, ['event_key' => 'trip-1']);

        $this->assertEquals(10, (int) $volunteer->fresh()->green_points);
        $this->assertEquals(1, RewardClaim::where('user_id', $volunteer->id)->count());
    }

    public function test_budget_cap_stops_payouts(): void
    {
        $this->campaign([
            'period' => RewardPeriod::Unlimited,
            'budget_total' => 750,
        ]);
        $user = $this->user();

        app(RewardService::class)->award($user, RewardTrigger::TripCompleted, ['event_key' => 'a']);
        app(RewardService::class)->award($user, RewardTrigger::TripCompleted, ['event_key' => 'b']);
        app(RewardService::class)->award($user, RewardTrigger::TripCompleted, ['event_key' => 'c']);

        $this->assertEquals(2, RewardClaim::where('user_id', $user->id)->count());
    }

    public function test_expired_campaign_is_not_awarded(): void
    {
        $this->campaign(['ends_at' => now()->subDay()]);
        $user = $this->user();

        $awarded = app(RewardService::class)->award($user, RewardTrigger::TripCompleted);

        $this->assertEmpty($awarded);
    }

    public function test_redeem_green_points_converts_to_cash(): void
    {
        $user = $this->user();
        $user->update(['green_points' => 100]);

        $naira = app(RewardService::class)->redeemGreenPoints($user, 50);

        $this->assertEquals(250.0, $naira);
        $this->assertEquals(50, (int) $user->fresh()->green_points);
        $this->assertEquals(1250.0, (float) $user->fresh()->wallet->cash_balance);
    }

    public function test_redeem_below_minimum_throws(): void
    {
        $user = $this->user();
        $user->update(['green_points' => 30]);

        $this->expectException(ValidationException::class);

        app(RewardService::class)->redeemGreenPoints($user, 20);
    }

    public function test_redeem_above_balance_throws(): void
    {
        $user = $this->user();
        $user->update(['green_points' => 20]);

        $this->expectException(ValidationException::class);

        app(RewardService::class)->redeemGreenPoints($user, 100);
    }

    public function test_admin_can_create_and_toggle_campaign(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/rewards')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/rewards/create')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/admin/rewards', [
                'name' => 'Eagles match bonus',
                'trigger' => 'trip_completed',
                'period' => 'once',
                'type' => 'earned',
                'value' => 200,
                'audience' => 'both',
                'budget_total' => 10000,
            ])
            ->assertRedirect();

        $campaign = RewardCampaign::where('name', 'Eagles match bonus')->firstOrFail();
        $this->assertTrue($campaign->active);

        $this->actingAs($admin)
            ->post("/admin/rewards/{$campaign->id}/toggle")
            ->assertRedirect();

        $this->assertFalse($campaign->fresh()->active);
    }

    public function test_rider_rewards_page_renders(): void
    {
        $user = $this->user();
        $user->update(['green_points' => 120]);

        $this->actingAs($user)
            ->get('/rewards')
            ->assertOk()
            ->assertSee('Green Points');
    }

    public function test_redeem_via_web_route(): void
    {
        $user = $this->user();
        $user->update(['green_points' => 100]);

        $this->actingAs($user)
            ->post('/rewards/redeem', ['points' => 50])
            ->assertRedirect();

        $this->assertEquals(50, (int) $user->fresh()->green_points);
        $this->assertEquals(1250.0, (float) $user->fresh()->wallet->cash_balance);
    }
}
