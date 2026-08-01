<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Models\ImpactStat;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\WorkplaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_users_are_created(): void
    {
        $this->seed([
            WorkplaceSeeder::class,
            DemoUserSeeder::class,
        ]);

        $this->assertSame(3, User::count());

        $driver = User::where('email', 'driver@workride.ng')->first();
        $this->assertNotNull($driver);
        $this->assertSame(VerificationLevel::DriverVerified, $driver->verification_level);
        $this->assertSame('ABJ-849-KJ', $driver->vehicles()->first()->plate_number);

        $volunteer = User::where('email', 'volunteer@workride.ng')->first();
        $this->assertSame(VerificationLevel::WorkplaceVerified, $volunteer->verification_level);

        $passenger = User::where('email', 'passenger@workride.ng')->first();
        $this->assertGreaterThan(0, (float) Wallet::where('user_id', $passenger->id)->value('subsidy_credits'));

        $impact = ImpactStat::where('user_id', $driver->id)->first();
        $this->assertGreaterThan(0, (int) $impact->total_trips);
    }
}
