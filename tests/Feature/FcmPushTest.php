<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Enums\VerificationLevel;
use App\Events\UserArrivedAtPickup;
use App\Models\Booking;
use App\Models\DeviceToken;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Notifications\UserArrivedAtPickupNotification;
use App\Services\FcmService;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FcmPushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['*' => Http::response([])]);
    }

    private function enablePush(): void
    {
        config([
            'workride.push.enabled' => true,
            'services.fcm.server_key' => 'test-key',
            'workride.push.arrived_radius_m' => 500,
        ]);
    }

    private function driver(): User
    {
        $driver = User::factory()->create([
            'role' => 'driver',
            'verification_level' => VerificationLevel::DriverVerified,
        ]);

        Vehicle::factory()->create(['user_id' => $driver->id]);
        Wallet::create(['user_id' => $driver->id]);

        return $driver;
    }

    private function passenger(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ], $overrides));
    }

    private function activeTrip(User $driver, array $overrides = []): Trip
    {
        return Trip::factory()->forDriver($driver)->create(array_merge([
            'status' => TripStatus::Active,
            'corridor' => Corridor::KubwaCbd,
            'route_name' => 'Kubwa → CBD',
            'current_lat' => 9.1117,
            'current_lng' => 7.3304,
        ], $overrides));
    }

    private function confirmedBooking(Trip $trip, User $passenger, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'trip_id' => $trip->id,
            'passenger_id' => $passenger->id,
            'pickup_lat' => 9.1117,
            'pickup_lng' => 7.3304,
            'status' => BookingStatus::Confirmed,
        ], $overrides));
    }

    // ---------- Device-token API ----------

    public function test_push_token_store_403_when_feature_disabled(): void
    {
        $user = $this->passenger();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/push/tokens', ['token' => 'tok-1'])
            ->assertForbidden();
    }

    public function test_push_token_store_registers_device_and_is_idempotent(): void
    {
        $this->enablePush();
        $user = $this->passenger();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/push/tokens', ['token' => 'tok-1', 'platform' => 'android'])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/push/tokens', ['token' => 'tok-1', 'platform' => 'android'])
            ->assertCreated();

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'tok-1',
            'platform' => 'android',
        ]);
    }

    public function test_push_token_store_rejects_invalid_platform(): void
    {
        $this->enablePush();
        $user = $this->passenger();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/push/tokens', ['token' => 'tok-1', 'platform' => 'pager'])
            ->assertUnprocessable();
    }

    public function test_push_token_destroy_forgets_device(): void
    {
        $this->enablePush();
        $user = $this->passenger();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-1']);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/push/tokens', ['token' => 'tok-1'])
            ->assertOk();

        $this->assertDatabaseMissing('device_tokens', ['user_id' => $user->id, 'token' => 'tok-1']);
    }

    public function test_push_token_routes_require_auth(): void
    {
        $this->enablePush();

        $this->postJson('/api/v1/push/tokens', ['token' => 'x'])->assertUnauthorized();
        $this->deleteJson('/api/v1/push/tokens', ['token' => 'x'])->assertUnauthorized();
    }

    // ---------- FcmService ----------

    public function test_send_to_user_hits_fcm_once_per_device(): void
    {
        $this->enablePush();
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['success' => 1, 'failure' => 0, 'results' => [['message_id' => 'm1']]]),
        ]);

        $user = $this->passenger();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-a']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-b']);

        app(FcmService::class)->sendToUser($user, 'Hi', 'Body', ['trip_id' => '1']);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['to'] === 'tok-a' && $request['notification']['title'] === 'Hi');
    }

    public function test_send_to_user_is_noop_when_push_disabled(): void
    {
        $user = $this->passenger();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-a']);

        $sent = app(FcmService::class)->sendToUser($user, 'Hi', 'Body');

        $this->assertSame(0, $sent);
        Http::assertNothingSent();
    }

    // ---------- TripService arrival nudges ----------

    public function test_arrival_nudge_fires_within_radius_and_is_idempotent(): void
    {
        $this->enablePush();
        Event::fake([UserArrivedAtPickup::class]);
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->passenger();
        $trip = $this->activeTrip($driver);
        $this->confirmedBooking($trip, $passenger);

        // Driver is exactly at the pickup point.
        app(TripService::class)->updateLocation($trip, $driver, 9.1117, 7.3304);

        $this->assertNotNull($trip->bookings()->first()->fresh()->arrival_notified_at);

        Event::assertDispatched(UserArrivedAtPickup::class, fn ($event) => $event->booking->passenger_id === $passenger->id);
        Notification::assertSentTo($passenger, UserArrivedAtPickupNotification::class);

        // Second update at the same point must not re-nudge (arrival_notified_at set).
        app(TripService::class)->updateLocation($trip->fresh(), $driver, 9.1117, 7.3304);

        Notification::assertSentToTimes($passenger, UserArrivedAtPickupNotification::class, 1);
    }

    public function test_arrival_nudge_skipped_outside_radius(): void
    {
        $this->enablePush();
        Event::fake([UserArrivedAtPickup::class]);
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->passenger();
        $trip = $this->activeTrip($driver);
        // Pickup ~2km away (9.14 lat ≈ 3.1km; use 9.135 for ~2.5km).
        $this->confirmedBooking($trip, $passenger, ['pickup_lat' => 9.14, 'pickup_lng' => 7.33]);

        app(TripService::class)->updateLocation($trip, $driver, 9.1117, 7.3304);

        $this->assertNull($trip->bookings()->first()->fresh()->arrival_notified_at);
        Event::assertNotDispatched(UserArrivedAtPickup::class);
        Notification::assertNothingSent();
    }

    public function test_arrival_nudge_skipped_for_non_active_trip(): void
    {
        $this->enablePush();
        Event::fake([UserArrivedAtPickup::class]);
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->passenger();
        $trip = $this->activeTrip($driver, ['status' => TripStatus::Scheduled]);
        $this->confirmedBooking($trip, $passenger);

        app(TripService::class)->updateLocation($trip, $driver, 9.1117, 7.3304);

        $this->assertNull($trip->bookings()->first()->fresh()->arrival_notified_at);
        Event::assertNotDispatched(UserArrivedAtPickup::class);
    }

    public function test_arrival_nudge_skipped_for_cancelled_booking(): void
    {
        $this->enablePush();
        Event::fake([UserArrivedAtPickup::class]);
        Notification::fake();

        $driver = $this->driver();
        $passenger = $this->passenger();
        $trip = $this->activeTrip($driver);
        $this->confirmedBooking($trip, $passenger, ['status' => BookingStatus::Cancelled]);

        app(TripService::class)->updateLocation($trip, $driver, 9.1117, 7.3304);

        $this->assertNull($trip->bookings()->first()->fresh()->arrival_notified_at);
        Event::assertNotDispatched(UserArrivedAtPickup::class);
        Notification::assertNothingSent();
    }
}
