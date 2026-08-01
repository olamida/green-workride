<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Enums\VerificationLevel;
use App\Jobs\CalculateImpactJob;
use App\Models\Booking;
use App\Models\ImpactStat;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Co2Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateImpactJobTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id]);

        return $user;
    }

    private function completedTrip(int $riders): Trip
    {
        $driver = $this->participant();
        $trip = Trip::factory()->create([
            'driver_id' => $driver->id,
            'corridor' => Corridor::KubwaCbd,
            'status' => TripStatus::Completed,
        ]);

        for ($i = 0; $i < $riders; $i++) {
            Booking::create([
                'trip_id' => $trip->id,
                'passenger_id' => $this->participant()->id,
                'status' => BookingStatus::Boarded,
                'fare_paid' => 600,
                'payment_method' => 'wallet',
            ]);
        }

        return $trip;
    }

    public function test_job_credits_driver_and_riders(): void
    {
        $trip = $this->completedTrip(2);
        $driver = $trip->driver;

        (new CalculateImpactJob($trip->id))->handle($this->app->make(Co2Service::class));

        // 3 occupants (driver + 2 riders), 22 km (Kubwa→CBD): co2 = 2*22*0.12 = 5.28.
        $this->assertSame(5.28, (float) ImpactStat::where('user_id', $driver->id)->value('co2_saved_kg'));
        $this->assertSame(1, (int) ImpactStat::where('user_id', $driver->id)->value('total_trips'));
        $this->assertSame(3, ImpactStat::count()); // driver + 2 riders
    }

    public function test_job_credits_each_boarded_passenger(): void
    {
        $trip = $this->completedTrip(2);

        (new CalculateImpactJob($trip->id))->handle($this->app->make(Co2Service::class));

        $this->assertSame(3, ImpactStat::count()); // driver + 2 passengers
        $this->assertTrue(ImpactStat::where('co2_saved_kg', 5.28)->exists());
    }

    public function test_no_impact_for_solo_driver(): void
    {
        $trip = $this->completedTrip(0);

        (new CalculateImpactJob($trip->id))->handle($this->app->make(Co2Service::class));

        $this->assertSame(0, ImpactStat::count());
    }

    public function test_cancelled_bookings_do_not_count(): void
    {
        $trip = $this->completedTrip(0);
        Booking::create([
            'trip_id' => $trip->id,
            'passenger_id' => $this->participant()->id,
            'status' => BookingStatus::Cancelled,
            'fare_paid' => 0,
            'payment_method' => 'free',
        ]);

        (new CalculateImpactJob($trip->id))->handle($this->app->make(Co2Service::class));

        $this->assertSame(0, ImpactStat::count());
    }

    public function test_missing_trip_is_noop(): void
    {
        (new CalculateImpactJob(999999))->handle($this->app->make(Co2Service::class));

        $this->assertSame(0, ImpactStat::count());
    }
}
