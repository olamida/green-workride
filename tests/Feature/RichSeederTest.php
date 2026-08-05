<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\DemandSurvey;
use App\Models\GtfsFeedMeta;
use App\Models\ImpactStat;
use App\Models\Junction;
use App\Models\OdSurvey;
use App\Models\P2pTransfer;
use App\Models\Payout;
use App\Models\ProbeDemandPoint;
use App\Models\RideCredit;
use App\Models\RoadEvent;
use App\Models\RoadSegment;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RichSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_database_seeder_builds_a_rich_demo_world(): void
    {
        $this->seed(DatabaseSeeder::class);

        // --- People, identity, vehicles.
        // Rich suite = 95 demo people + 5 workplace admins; plus the legacy
        // AdminUserSeeder admin + 3 DemoUserSeeder accounts = 104 users total.
        $this->assertSame(100, User::where('email', 'like', 'demo%@workride.ng')->count());
        $this->assertSame(104, User::count());
        $this->assertSame(41, Vehicle::count()); // 40 rich + 1 legacy DemoUserSeeder vehicle

        $l3 = User::where('verification_level', 3)->where('email', 'like', 'demo%@workride.ng')->count();
        $this->assertGreaterThanOrEqual(45, $l3);

        // --- Trips & bookings. ---
        $this->assertSame(80, Trip::count());
        $this->assertSame(10, Trip::where('status', 'active')->count());
        $this->assertSame(22, Trip::where('status', 'scheduled')->count());
        $this->assertSame(40, Trip::where('status', 'completed')->count());
        $this->assertSame(8, Trip::where('status', 'cancelled')->count());

        $this->assertGreaterThanOrEqual(100, Booking::count());
        $uniquePairs = Booking::query()
            ->selectRaw('count(*) as c')
            ->groupBy('trip_id', 'passenger_id')
            ->having('c', '>', 1)
            ->count();
        $this->assertSame(0, $uniquePairs, 'no duplicate (trip_id, passenger_id) bookings');

        // --- Money. ---
        $this->assertSame(0, Wallet::whereRaw('cash_balance < 0')->orWhereRaw('earned_balance < 0')->count());
        $this->assertSame(30, RideCredit::count());
        $this->assertSame(40, P2pTransfer::count());
        $this->assertSame(20, Payout::count());

        // --- Road intelligence. ---
        $this->assertGreaterThanOrEqual(100, RoadEvent::count());
        $this->assertSame(30, RoadEvent::where('is_confirmed', true)->count());
        $this->assertSame(20, RoadSegment::count());

        // --- Demand research field kit. ---
        $this->assertGreaterThanOrEqual(40, Junction::count());
        $this->assertGreaterThanOrEqual(80, DemandSurvey::count());
        $this->assertSame(30, ProbeDemandPoint::count());
        $this->assertSame(25, OdSurvey::count());

        // --- Impact + GTFS. ---
        $this->assertGreaterThan(0, ImpactStat::count());
        $this->assertGreaterThan(0, ChatMessage::count());
        $this->assertNotNull(GtfsFeedMeta::find(1));

        // --- Invariants: wallets exist for every demo user. ---
        $this->assertSame(
            User::where('email', 'like', 'demo%@workride.ng')->count(),
            Wallet::whereHas('user', fn ($q) => $q->where('email', 'like', 'demo%@workride.ng'))->count()
        );
    }
}
