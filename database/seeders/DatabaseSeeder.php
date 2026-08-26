<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Multi-tenant foundation (must run first for city-scoped seeders)
            CountrySeeder::class,
            CitySeeder::class,

            WorkplaceSeeder::class,
            GtfsStopSeeder::class,
            AdminUserSeeder::class,
            DemoUserSeeder::class,
            Sprint8DemoSeeder::class,
            DemoMissionSeeder::class,
            DemoOpsSeeder::class,

            // Rich demo data suite (guide WORKRIDE-PROMPT-SEEDING-DATA.md).
            // Each Rich* seeder is idempotent (guarded by the demo001 marker),
            // so re-running db:seed is always safe.
            JunctionSeeder::class,
            RichUserSeeder::class,
            RichVerificationSeeder::class,
            RichVehicleSeeder::class,
            RichWalletSeeder::class,
            RichTripSeeder::class,
            RichBookingSeeder::class,
            RichRideCreditSeeder::class,
            RichTransferSeeder::class,
            RichRoadSeeder::class,
            RichDemandSeeder::class,
            RichGtfsSeeder::class,
            RichChatImpactSeeder::class,
        ]);
    }
}
