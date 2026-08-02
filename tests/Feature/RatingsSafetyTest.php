<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\RideRating;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BookingService;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RatingsSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'passenger', VerificationLevel $level = VerificationLevel::WorkplaceVerified, array $extra = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => UserRole::from($role),
            'verification_level' => $level,
        ], $extra));

        Wallet::create(['user_id' => $user->id, 'cash_balance' => 1000]);

        return $user;
    }

    private function completedRide(): array
    {
        $driver = $this->user('driver', VerificationLevel::DriverVerified);
        $passenger = $this->user('passenger');
        $trip = Trip::factory()->forDriver($driver)->create([
            'status' => TripStatus::Completed,
            'departure_time' => now()->subHour()->floorMinute(),
        ]);
        $booking = Booking::factory()->create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Completed,
        ]);

        return [$driver, $passenger, $trip, $booking];
    }

    public function test_guest_is_redirected_from_ratings(): void
    {
        [$driver, $passenger, $trip, $booking] = $this->completedRide();

        $this->post("/ratings/{$booking->id}")->assertRedirect('/login');
    }

    public function test_passenger_rates_driver_after_completed_trip(): void
    {
        [$driver, $passenger, $trip, $booking] = $this->completedRide();

        $this->actingAs($passenger)
            ->post("/ratings/{$booking->id}", ['rating' => 5, 'note' => 'On time and careful'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $rating = RideRating::firstOrFail();
        $this->assertEquals($booking->id, $rating->booking_id);
        $this->assertEquals($passenger->id, $rating->rater_id);
        $this->assertEquals($driver->id, $rating->ratee_id);
        $this->assertEquals(5, $rating->rating);
        $this->assertEquals('On time and careful', $rating->note);

        $this->assertDatabaseHas('activity_logs', ['action' => 'rated_ride']);

        $this->assertEquals(5.0, app(RatingService::class)->averageFor($driver));
    }

    public function test_rating_is_once_per_booking(): void
    {
        [$driver, $passenger, $trip, $booking] = $this->completedRide();

        $this->actingAs($passenger)->post("/ratings/{$booking->id}", ['rating' => 4]);
        $this->actingAs($passenger)->post("/ratings/{$booking->id}", ['rating' => 3])
            ->assertSessionHasErrors();

        $this->assertEquals(1, RideRating::count());
    }

    public function test_driver_rates_passenger(): void
    {
        [$driver, $passenger, $trip, $booking] = $this->completedRide();

        $this->actingAs($driver)
            ->post("/ratings/{$booking->id}", ['rating' => 4])
            ->assertRedirect();

        $rating = RideRating::firstOrFail();
        $this->assertEquals($driver->id, $rating->rater_id);
        $this->assertEquals($passenger->id, $rating->ratee_id);
    }

    public function test_cannot_rate_a_strangers_ride(): void
    {
        [, , , $booking] = $this->completedRide();
        $stranger = $this->user('passenger');

        $this->actingAs($stranger)
            ->post("/ratings/{$booking->id}", ['rating' => 5])
            ->assertSessionHasErrors();

        $this->assertEquals(0, RideRating::count());
    }

    public function test_cannot_rate_a_ride_that_has_not_completed(): void
    {
        $driver = $this->user('driver', VerificationLevel::DriverVerified);
        $passenger = $this->user('passenger');
        $trip = Trip::factory()->forDriver($driver)->create(['status' => TripStatus::Scheduled]);
        $booking = Booking::factory()->create([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs($passenger)
            ->post("/ratings/{$booking->id}", ['rating' => 5])
            ->assertSessionHasErrors();

        $this->assertEquals(0, RideRating::count());
    }

    public function test_rating_validation(): void
    {
        [, , , $booking] = $this->completedRide();

        $this->actingAs($booking->passenger)
            ->post("/ratings/{$booking->id}", ['rating' => 6])
            ->assertSessionHasErrors('rating');
    }

    public function test_admin_ratings_dashboard_shows_scoreboard(): void
    {
        [, $passenger, $trip, $booking] = $this->completedRide();
        $driver = $trip->driver;

        $this->actingAs($passenger)->post("/ratings/{$booking->id}", ['rating' => 5]);

        $admin = $this->user('admin', VerificationLevel::Unverified, ['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/ratings')
            ->assertOk()
            ->assertSee('Driver scoreboard')
            ->assertSee($driver->name)
            ->assertSee('★');

        $this->actingAs($passenger)->get('/admin/ratings')->assertForbidden();
    }

    public function test_women_only_trip_blocks_non_female_booking(): void
    {
        $driver = $this->user('driver', VerificationLevel::DriverVerified);
        $trip = Trip::factory()->forDriver($driver)->create(['women_only' => true]);
        $male = $this->user('passenger', VerificationLevel::WorkplaceVerified, ['gender' => 'male']);

        $this->expectException(ValidationException::class);
        app(BookingService::class)->book($trip, $male, ['payment_method' => 'wallet']);
    }

    public function test_women_only_trip_allows_female_booking(): void
    {
        $driver = $this->user('driver', VerificationLevel::DriverVerified);
        $trip = Trip::factory()->forDriver($driver)->create(['women_only' => true]);
        $female = $this->user('passenger', VerificationLevel::WorkplaceVerified, ['gender' => 'female']);

        $booking = app(BookingService::class)->book($trip, $female, ['payment_method' => 'wallet']);

        $this->assertNotNull($booking->id);
        $this->assertEquals($female->id, $booking->passenger_id);
    }

    public function test_board_defaults_women_only_filter_from_profile(): void
    {
        $driver = $this->user('driver', VerificationLevel::DriverVerified);
        $passenger = $this->user('passenger', VerificationLevel::WorkplaceVerified, [
            'gender' => 'female',
            'prefers_women_only' => true,
        ]);

        Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'women_only' => true,
            'departure_time' => now()->addHour()->floorMinute(),
        ]);
        Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'women_only' => false,
            'departure_time' => now()->addHour()->floorMinute(),
        ]);

        $response = $this->actingAs($passenger)->get('/trips');

        $response->assertOk();
        $response->assertSee('Women-only');
    }

    public function test_share_page_is_public(): void
    {
        $driver = $this->user('driver', VerificationLevel::DriverVerified);
        $trip = Trip::factory()->forDriver($driver)->create([
            'status' => TripStatus::Scheduled,
            'departure_time' => now()->addHour()->floorMinute(),
        ]);

        $this->get("/trips/{$trip->id}/share")
            ->assertOk()
            ->assertSee($trip->route_name)
            ->assertSee($driver->name);
    }

    public function test_share_page_404_for_completed_trip(): void
    {
        [$driver] = $this->completedRide();
        $trip = Trip::factory()->forDriver($driver)->create(['status' => TripStatus::Completed]);

        $this->get("/trips/{$trip->id}/share")->assertNotFound();
    }

    public function test_sos_writes_audit_log_for_participants(): void
    {
        [$driver, $passenger, $trip] = $this->completedRide();

        $this->actingAs($passenger)
            ->post("/trips/{$trip->id}/sos", ['lat' => 9.05, 'lng' => 7.45])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sos',
            'model_type' => Trip::class,
            'model_id' => $trip->id,
        ]);
    }

    public function test_sos_blocked_for_non_participants(): void
    {
        [, , $trip] = $this->completedRide();
        $stranger = $this->user('passenger');

        $this->actingAs($stranger)
            ->post("/trips/{$trip->id}/sos")
            ->assertForbidden();

        $this->assertEquals(0, ActivityLog::where('action', 'sos')->count());
    }

    public function test_profile_update_saves_safety_and_preference(): void
    {
        $user = $this->user('passenger');

        $this->actingAs($user)
            ->post('/profile', [
                'name' => 'Fatima Yusuf',
                'phone' => '08011111111',
                'gender' => 'female',
                'prefers_women_only' => '1',
                'emergency_contact_name' => 'Adamu Yusuf',
                'emergency_contact_phone' => '08022222222',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertEquals('female', $user->gender);
        $this->assertTrue($user->prefers_women_only);
        $this->assertEquals('Adamu Yusuf', $user->emergency_contact_name);
        $this->assertEquals('08022222222', $user->emergency_contact_phone);
    }

    public function test_offline_page_renders_for_guests(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Your connection dropped');
    }

    public function test_landing_kpis_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Scheduled trips leaving now');
    }

    public function test_service_worker_offline_fallback(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertSee('/offline', false)
            ->assertSee('mode === \'navigate\'', false);
    }
}
