<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\EmployerCoverageType;
use App\Enums\EmployerMemberStatus;
use App\Enums\EmployerProgramType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Services\EmployerLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.employer_programs.enabled' => true]);
    }

    private function driver(): User
    {
        $driver = User::factory()->create([
            'role' => UserRole::Driver,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $driver->id]);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function passenger(float $cash = 2000): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id, 'cash_balance' => $cash]);

        return $user;
    }

    private function employer(array $overrides = []): Employer
    {
        $employer = Employer::create(array_merge([
            'name' => 'Test Corp',
            'zone' => 'CBD',
            'program_type' => EmployerProgramType::Full,
            'corridors' => ['kubwa_cbd'],
            'active' => true,
        ], $overrides));

        app(EmployerLedgerService::class)->fund($employer, 5000.00, 'TEST-FUND-'.$employer->id);

        return $employer;
    }

    private function bookableTrip(float $fare = 600): Trip
    {
        return Trip::factory()->forDriver($this->driver())->create([
            'corridor' => Corridor::KubwaCbd,
            'fare_per_seat' => $fare,
            'total_seats' => 4,
            'available_seats' => 4,
            'departure_time' => now()->addMinutes(30),
        ]);
    }

    public function test_feature_gated_off_no_coverage(): void
    {
        config(['workride.employer_programs.enabled' => false]);

        $employer = $this->employer();
        $passenger = $this->passenger();
        $this->enroll($employer, $passenger);

        $booking = $this->book($this->bookableTrip(), $passenger);

        $this->assertNull($booking->employer_id);
        $this->assertEquals(0.0, (float) $booking->employer_contribution);
    }

    public function test_full_coverage_employer_pays_entire_fare(): void
    {
        $employer = $this->employer();
        $passenger = $this->passenger();
        $this->enroll($employer, $passenger);

        $booking = $this->book($this->bookableTrip(600), $passenger);

        $this->assertEquals($employer->id, $booking->employer_id);
        $this->assertEquals(600.0, (float) $booking->employer_contribution);
        $this->assertEquals(EmployerCoverageType::Full, $booking->employer_coverage);
        $this->assertEquals(600.0, (float) $booking->fare_paid);
        $this->assertEquals(2000.0, (float) $passenger->fresh()->wallet->cash_balance);
    }

    public function test_unenrolled_staff_get_no_coverage(): void
    {
        $this->employer();
        $passenger = $this->passenger();

        $booking = $this->book($this->bookableTrip(600), $passenger);

        $this->assertNull($booking->employer_id);
        $this->assertEquals(0.0, (float) $booking->employer_contribution);
    }

    public function test_insufficient_employer_funds_stops_coverage(): void
    {
        $employer = $this->employer();
        $employer->wallet()->first()->update(['cash_balance' => 100]);

        $passenger = $this->passenger();
        $this->enroll($employer, $passenger);

        $booking = $this->book($this->bookableTrip(600), $passenger);

        $this->assertEquals(0.0, (float) $booking->employer_contribution);
    }

    public function test_one_way_employer_covers_only_covered_direction(): void
    {
        // CBD employer covers "to work" — the kubwa_cbd corridor ends at CBD.
        $employer = $this->employer([
            'program_type' => EmployerProgramType::OneWay,
            'covered_direction' => 'to_work',
        ]);
        $passenger = $this->passenger();
        $this->enroll($employer, $passenger);

        $booking = $this->book($this->bookableTrip(600), $passenger);

        $this->assertEquals(600.0, (float) $booking->employer_contribution);

        // A NYANYA employer covering "to work" does NOT cover the CBD-bound kubwa_cbd leg.
        $reverse = Employer::create([
            'name' => 'Reverse Corp',
            'zone' => 'NYANYA',
            'program_type' => EmployerProgramType::OneWay,
            'covered_direction' => 'to_work',
            'corridors' => ['kubwa_cbd'],
            'active' => true,
        ]);
        app(EmployerLedgerService::class)->fund($reverse, 5000.00, 'TEST-FUND-'.$reverse->id);

        $reversePassenger = $this->passenger();
        $this->enroll($reverse, $reversePassenger);

        $booking2 = $this->book($this->bookableTrip(600), $reversePassenger);

        $this->assertNull($booking2->employer_id);
        $this->assertEquals(0.0, (float) $booking2->employer_contribution);
    }

    public function test_percent_and_capped_policies(): void
    {
        $percent = $this->employer([
            'name' => 'Percent Corp',
            'program_type' => EmployerProgramType::Percent,
            'percent_covered' => 50,
        ]);
        $passenger = $this->passenger();
        $this->enroll($percent, $passenger);

        $booking = $this->book($this->bookableTrip(600), $passenger, $passenger->fresh());
        $this->assertEquals(300.0, (float) $booking->employer_contribution);
        $this->assertEquals(EmployerCoverageType::Percent, $booking->employer_coverage);

        $capped = $this->employer([
            'name' => 'Capped Corp',
            'program_type' => EmployerProgramType::Capped,
            'max_per_trip' => 250,
        ]);
        $cappedPassenger = $this->passenger();
        $this->enroll($capped, $cappedPassenger);

        $booking2 = $this->book($this->bookableTrip(600), $cappedPassenger);
        $this->assertEquals(250.0, (float) $booking2->employer_contribution);
    }

    public function test_monthly_cap_limits_coverage(): void
    {
        $employer = $this->employer([
            'name' => 'Capped Monthly Corp',
            'max_monthly_per_employee' => 500,
        ]);
        $passenger = $this->passenger(5000);
        $this->enroll($employer, $passenger);

        $booking = $this->book($this->bookableTrip(600), $passenger);
        $this->assertEquals(500.0, (float) $booking->employer_contribution);

        $booking2 = $this->book($this->bookableTrip(600), $passenger, $passenger->fresh());
        $this->assertEquals(0.0, (float) $booking2->employer_contribution);
    }

    public function test_cancel_refunds_employer_coverage(): void
    {
        $employer = $this->employer();
        $passenger = $this->passenger();
        $this->enroll($employer, $passenger);

        $booking = $this->book($this->bookableTrip(600), $passenger);
        $this->assertEquals(5000.0, (float) $employer->wallet()->first()->fresh()->cash_balance);

        $this->actingAs($passenger)
            ->post("/bookings/{$booking->id}/cancel")
            ->assertRedirect();

        $this->assertEquals(5000.0, (float) $employer->wallet()->first()->fresh()->cash_balance);
        $this->assertEquals(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_csv_enrollment_skips_unknown_emails(): void
    {
        $employer = $this->employer();
        $passenger = $this->passenger();

        $file = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($file, "email,employee_id\n{$passenger->email},EMP-1\nunknown@nowhere.ng,EMP-2\n");

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post("/admin/employers/{$employer->id}/enroll", ['csv' => new UploadedFile($file, 'enroll.csv', 'text/csv', null, true)])
            ->assertRedirect();

        @unlink($file);

        $this->assertDatabaseHas('employer_members', [
            'employer_id' => $employer->id,
            'user_id' => $passenger->id,
            'status' => EmployerMemberStatus::Active->value,
        ]);
        $this->assertDatabaseCount('employer_members', 1);
    }

    public function test_admin_can_create_fund_and_view_employer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/employers')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/employers/create')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/admin/employers', [
                'name' => 'FMF Demo',
                'zone' => 'CBD',
                'program_type' => 'full',
                'corridors' => ['kubwa_cbd'],
            ])
            ->assertRedirect();

        $employer = Employer::where('name', 'FMF Demo')->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/employers/{$employer->id}/fund", ['amount' => 250000])
            ->assertRedirect();

        $this->assertEquals(250000.0, (float) $employer->wallet()->first()->fresh()->cash_balance);

        $this->actingAs($admin)
            ->get("/admin/employers/{$employer->id}")
            ->assertOk();
    }

    public function test_non_admin_cannot_manage_employers(): void
    {
        $passenger = $this->passenger();

        $this->actingAs($passenger)
            ->get('/admin/employers')
            ->assertForbidden();
    }

    private function enroll(Employer $employer, User $user): void
    {
        EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Active->value,
        ]);
    }

    private function book(Trip $trip, User $passenger, ?User $acting = null)
    {
        $acting = $acting ?? $passenger;

        $this->actingAs($acting)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertRedirect();

        return $trip->bookings()->where('passenger_id', $passenger->id)->firstOrFail();
    }
}
