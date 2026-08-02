<?php

namespace Database\Seeders;

use App\Enums\MissionActivityType;
use App\Enums\MissionStatus;
use App\Enums\MissionVerificationMode;
use App\Enums\RewardType;
use App\Enums\SponsorType;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo missions for funding/demo pitches (guide §9B + §8 stakeholder).
 *
 * Wired into DatabaseSeeder so a fresh `db:seed` gives promoters (FCT Admin,
 * FERMA, NURTW cooperative) a visible "what a promoted activity looks like"
 * on the rider hub + Control Tower. Gated: no-ops when FEATURE_MISSIONS is off.
 */
class DemoMissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('workride.missions.enabled', false)) {
            return;
        }

        $admin = User::where('email', config('workride.admin.email'))->first();

        $missions = [
            [
                'name' => 'Give 5 free rides this week',
                'description' => 'Kubwa–CBD volunteers: carry passengers for free and earn ₦2,000 cash once you complete 5 volunteer trips.',
                'sponsor_type' => SponsorType::Community,
                'sponsor_name' => 'WorkRide Community Trust',
                'activity_type' => MissionActivityType::VolunteerRides,
                'metric_goal' => 5,
                'metric_window_days' => 7,
                'reward_type' => RewardType::Cash,
                'reward_value' => 2000,
                'verification_mode' => MissionVerificationMode::Auto,
                'instructions' => 'Publish free rides on any corridor and complete them. The app counts each completed trip automatically.',
                'budget_total' => 200000,
                'status' => MissionStatus::Published,
            ],
            [
                'name' => 'Crowd-source Kubwa potholes',
                'description' => 'Help FERMA map the worst roads. Report 3 potholes that get confirmed by other drivers and earn ₦1,000 Green Points.',
                'sponsor_type' => SponsorType::Government,
                'sponsor_name' => 'FERMA (via WorkRide Road Lab)',
                'activity_type' => MissionActivityType::PotholesConfirmed,
                'metric_goal' => 3,
                'metric_window_days' => 30,
                'reward_type' => RewardType::GreenPoints,
                'reward_value' => 1000,
                'verification_mode' => MissionVerificationMode::Auto,
                'instructions' => 'Drive with the sensor on (trips only). Your phone records Z-axis hits; 5 reports within 20m confirm a pothole.',
                'budget_total' => 100000,
                'status' => MissionStatus::Published,
            ],
            [
                'name' => 'Community clean-up (Jabi)',
                'description' => 'Help the Jabi Park community. Submit a photo of your clean-up participation for the NURTW Jabi chapter to verify.',
                'sponsor_type' => SponsorType::Private,
                'sponsor_name' => 'NURTW Jabi',
                'activity_type' => MissionActivityType::Custom,
                'metric_goal' => 1,
                'metric_window_days' => 30,
                'reward_type' => RewardType::Earned,
                'reward_value' => 1500,
                'verification_mode' => MissionVerificationMode::Proof,
                'proof_label' => 'Photo of you at the Jabi clean-up site',
                'instructions' => 'Take a selfie at the event with the banner visible, then submit it here for review.',
                'budget_total' => 50000,
                'status' => MissionStatus::Published,
            ],
        ];

        foreach ($missions as $data) {
            $data['slug'] = Str::slug($data['name']).'-'.strtolower(Str::random(5));
            $data['created_by'] = $admin?->id;
            $data['budget_spent'] = 0;

            Mission::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
