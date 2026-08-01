<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Enums\VerificationLevel;
use App\Models\ImpactStat;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Models\Workplace;
use Illuminate\Database\Seeder;

/**
 * Demo accounts for the Rider PWA and Ops Control Tower demos/funding pitches.
 *
 * Passwords are intentionally weak and documented in the guide — every account
 * is demo-only and must be changed/deleted before any production data lands.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $workplace = Workplace::query()
            ->where('acronym', 'FMF')
            ->first() ?? Workplace::first();

        if (! $workplace) {
            $this->command?->error('No workplace seeded — run WorkplaceSeeder first.');

            return;
        }

        $password = config('workride.demo.password', 'demo1234');

        $users = [
            // Level-3 paid driver on Kubwa→CBD with a verified coaster.
            'driver@workride.ng' => [
                'name' => 'Aisha Bello',
                'phone' => '+2348010000001',
                'role' => UserRole::Driver,
                'verification_level' => VerificationLevel::DriverVerified,
                'workplace_id' => $workplace->id,
                'wallet' => ['cash_balance' => 12450.00, 'subsidy_credits' => 0],
                'vehicle' => [
                    'plate_number' => 'ABJ-849-KJ',
                    'make' => 'Toyota',
                    'model' => 'Hiace Coaster',
                    'color' => 'Forest Green',
                    'seats' => 18,
                    'type' => VehicleType::Coaster,
                    'papers_verified' => true,
                ],
                'impact' => ['total_trips' => 42, 'co2_saved_kg' => 756.00, 'fuel_saved_litres' => 210.00, 'trees_equivalent' => 36.00, 'level' => 4],
            ],
            // Free volunteer rider — the supply bootstrap for the fuel crisis.
            'volunteer@workride.ng' => [
                'name' => 'Chinedu Okafor',
                'phone' => '+2348010000002',
                'role' => UserRole::Volunteer,
                'verification_level' => VerificationLevel::WorkplaceVerified,
                'workplace_id' => $workplace->id,
                'wallet' => ['cash_balance' => 0, 'subsidy_credits' => 0],
                'impact' => ['total_trips' => 15, 'co2_saved_kg' => 180.00, 'fuel_saved_litres' => 75.00, 'trees_equivalent' => 8.57, 'level' => 2],
            ],
            // Level-1 passenger with subsidy credits (the palliative story).
            'passenger@workride.ng' => [
                'name' => 'Fatima Yusuf',
                'phone' => '+2348010000003',
                'role' => UserRole::Passenger,
                'verification_level' => VerificationLevel::WorkplaceVerified,
                'workplace_id' => $workplace->id,
                'wallet' => ['cash_balance' => 3200.00, 'subsidy_credits' => 15000.00],
                'impact' => ['total_trips' => 28, 'co2_saved_kg' => 336.00, 'fuel_saved_litres' => 140.00, 'trees_equivalent' => 16.00, 'level' => 3],
            ],
        ];

        foreach ($users as $email => $data) {
            $walletData = $data['wallet'] ?? ['cash_balance' => 0, 'subsidy_credits' => 0];
            $vehicleData = $data['vehicle'] ?? null;
            $impactData = $data['impact'] ?? ['total_trips' => 0, 'co2_saved_kg' => 0, 'fuel_saved_litres' => 0, 'trees_equivalent' => 0, 'level' => 1];

            unset($data['wallet'], $data['vehicle'], $data['impact']);

            $user = User::updateOrCreate(
                ['email' => $email],
                array_merge($data, [
                    'password' => $password,
                    'is_banned' => false,
                    'nin_last4' => substr(str_shuffle('0123456789'), 0, 4),
                    'nin_hash' => hash('sha256', 'demo-'.$email),
                ]),
            );

            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                $walletData,
            );

            if ($vehicleData) {
                Vehicle::updateOrCreate(
                    ['user_id' => $user->id, 'plate_number' => $vehicleData['plate_number']],
                    $vehicleData,
                );
            }

            ImpactStat::updateOrCreate(
                ['user_id' => $user->id],
                $impactData,
            );

            $this->command?->info(sprintf('Demo user ready: %s / %s (%s)', $email, $password, $data['name']));
        }
    }
}
