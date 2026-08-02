<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\RemittanceStatus;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\StakeholderRemittance;
use App\Models\Trip;
use App\Models\Union;
use App\Models\User;
use App\Services\StakeholderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StakeholderRemittanceTest extends TestCase
{
    use RefreshDatabase;

    private function union(): Union
    {
        return Union::create([
            'name' => 'NURTW Kubwa Park',
            'corridor' => 'kubwa_cbd',
            'commission_rate' => 0.05,
            'is_active' => true,
        ]);
    }

    private function tripWithCarriedBooking(User $driver): Trip
    {
        $passenger = User::factory()->create();

        $trip = Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'status' => TripStatus::Completed,
            'fare_per_seat' => 600,
        ]);

        $booking = Booking::factory()->create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Boarded,
            'fare_paid' => 600,
            'payment_method' => 'wallet',
        ]);

        return $trip->fresh()->load('bookings');
    }

    public function test_paid_carried_booking_records_a_pending_remittance(): void
    {
        $this->union();
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        $trip = $this->tripWithCarriedBooking($driver);
        $count = app(StakeholderService::class)->recordForTrip($trip);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('stakeholder_remittances', [
            'trip_id' => $trip->id,
            'status' => RemittanceStatus::Pending->value,
            'reference' => 'REM-'.$trip->bookings->first()->id,
        ]);
    }

    public function test_volunteer_rides_remit_nothing(): void
    {
        $this->union();
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        $passenger = User::factory()->create();
        $trip = Trip::factory()->forDriver($driver)->volunteer()->create([
            'corridor' => Corridor::KubwaCbd,
            'status' => TripStatus::Completed,
        ]);
        Booking::factory()->create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Boarded,
            'fare_paid' => 0,
            'payment_method' => 'free',
        ]);

        $count = app(StakeholderService::class)->recordForTrip($trip->fresh()->load('bookings'));

        $this->assertSame(0, $count);
        $this->assertDatabaseMissing('stakeholder_remittances', ['trip_id' => $trip->id]);
    }

    public function test_record_is_idempotent(): void
    {
        $this->union();
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        $trip = $this->tripWithCarriedBooking($driver);
        $service = app(StakeholderService::class);

        $service->recordForTrip($trip);
        $count = $service->recordForTrip($trip);

        $this->assertSame(0, $count);
        $this->assertSame(1, StakeholderRemittance::where('trip_id', $trip->id)->count());
    }

    public function test_settle_marks_pending_remittances_paid(): void
    {
        $this->union();
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        $trip = $this->tripWithCarriedBooking($driver);
        $service = app(StakeholderService::class);
        $service->recordForTrip($trip);

        $settled = $service->settleDue();

        $this->assertSame(1, $settled);
        $this->assertDatabaseHas('stakeholder_remittances', [
            'trip_id' => $trip->id,
            'status' => RemittanceStatus::Paid->value,
        ]);
        $this->assertNotNull(StakeholderRemittance::first()->paid_at);
    }

    public function test_union_lookup_prefers_corridor_match(): void
    {
        $this->union();
        Union::create([
            'name' => 'Generic Chapter',
            'corridor' => null,
            'commission_rate' => 0.05,
            'is_active' => true,
        ]);

        $union = app(StakeholderService::class)->unionFor('kubwa_cbd');

        $this->assertSame('NURTW Kubwa Park', $union->name);
    }
}
