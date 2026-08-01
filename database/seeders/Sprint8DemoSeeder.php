<?php

namespace Database\Seeders;

use App\Enums\CommodityCategory;
use App\Enums\EmployerMemberStatus;
use App\Enums\EmployerProgramType;
use App\Enums\RewardAudience;
use App\Enums\RewardPeriod;
use App\Enums\RewardTrigger;
use App\Enums\RewardType;
use App\Models\Commodity;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\RewardCampaign;
use App\Models\User;
use App\Models\Workplace;
use App\Services\EmployerLedgerService;
use Illuminate\Database\Seeder;

/**
 * Sprint 8 demo data: one Corporate Mobility Program (FMF) with funded wallet
 * and enrolled staff, a sponsor reward campaign, and the commodity catalog
 * (gold, rice, maize, fuel) — everything the Control Tower + Rider PWA need
 * for the funding-pitch demo.
 */
class Sprint8DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedEmployerProgram();
        $this->seedRewardCampaign();
        $this->seedCommodities();
    }

    private function seedEmployerProgram(): void
    {
        $workplace = Workplace::query()->where('acronym', 'FMF')->first()
            ?? Workplace::first();

        $employer = Employer::updateOrCreate(
            ['name' => 'Federal Ministry of Finance (FMF)'],
            [
                'email' => 'mobility@fmf.gov.ng',
                'phone' => '+2349000000001',
                'rc_number' => null,
                'address' => 'Federal Ministry of Finance HQ, Central Business District, Abuja',
                'zone' => 'CBD',
                'workplace_id' => $workplace?->id,
                'program_type' => EmployerProgramType::Full,
                'max_monthly_per_employee' => 50000,
                'corridors' => ['kubwa_cbd', 'nyanya_idu', 'lugbe_cbd'],
                'active' => true,
            ]
        );

        $ledger = app(EmployerLedgerService::class);
        $ledger->fund($employer, 2000000.00, 'EMP-FUND-SEED-'.$employer->id, 'Seed funding — corporate mobility demo');

        foreach (['driver@workride.ng', 'passenger@workride.ng', 'volunteer@workride.ng'] as $email) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                continue;
            }

            EmployerMember::updateOrCreate(
                ['employer_id' => $employer->id, 'user_id' => $user->id],
                ['employee_id' => 'FMF-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'status' => EmployerMemberStatus::Active->value]
            );
        }
    }

    private function seedRewardCampaign(): void
    {
        RewardCampaign::updateOrCreate(
            ['name' => 'Volunteer ride green points'],
            [
                'description' => 'Every volunteer free ride earns 10 Green Points (guide §6 Workflow 2 supply bootstrap).',
                'audience' => RewardAudience::Volunteers,
                'trigger' => RewardTrigger::VolunteerRide,
                'reward_type' => RewardType::GreenPoints,
                'reward_value' => 10,
                'period' => RewardPeriod::Unlimited,
                'active' => true,
            ]
        );

        RewardCampaign::updateOrCreate(
            ['name' => '5 rides a week bonus'],
            [
                'description' => 'Complete 5 rides in a week → ₦500 earned balance.',
                'audience' => RewardAudience::Both,
                'trigger' => RewardTrigger::WeeklyFiveRides,
                'reward_type' => RewardType::Earned,
                'reward_value' => 500,
                'period' => RewardPeriod::Weekly,
                'budget_total' => 50000,
                'active' => true,
            ]
        );
    }

    private function seedCommodities(): void
    {
        Commodity::updateOrCreate(
            ['symbol' => 'AU'],
            ['name' => 'Gold', 'category' => CommodityCategory::PreciousMetal, 'unit' => 'gram', 'current_price_ngn' => 98000.00, 'is_tradable' => true, 'is_shop_item' => false, 'active' => true]
        );

        Commodity::updateOrCreate(
            ['symbol' => 'RICE50'],
            ['name' => 'Rice (50kg)', 'category' => CommodityCategory::Agricultural, 'unit' => 'bag', 'current_price_ngn' => 88000.00, 'is_tradable' => true, 'is_shop_item' => true, 'active' => true]
        );

        Commodity::updateOrCreate(
            ['symbol' => 'MAIZE'],
            ['name' => 'Maize', 'category' => CommodityCategory::Agricultural, 'unit' => 'kg', 'current_price_ngn' => 950.00, 'is_tradable' => true, 'is_shop_item' => true, 'active' => true]
        );

        Commodity::updateOrCreate(
            ['symbol' => 'FUEL'],
            ['name' => 'Fuel voucher', 'category' => CommodityCategory::Fuel, 'unit' => 'litre', 'current_price_ngn' => 1250.00, 'is_tradable' => false, 'is_shop_item' => true, 'active' => true]
        );
    }
}
