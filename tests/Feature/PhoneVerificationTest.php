<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\PhoneOtp;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Notifications\SendPhoneOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.phone_verification.enabled' => true]);
        Notification::fake();
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'phone' => '+2348123456789',
            'verification_level' => VerificationLevel::Unverified,
        ], $overrides));
    }

    private function sendCode(User $user, ?string $phone = null): string
    {
        $payload = $phone !== null ? ['phone' => $phone] : [];

        $this->actingAs($user)
            ->post('/verify/phone', $payload)
            ->assertRedirect();

        return Notification::sent($user, SendPhoneOtp::class)->last()->code;
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

    public function test_phone_page_requires_auth(): void
    {
        $this->get('/verify/phone')->assertRedirect('/login');
    }

    public function test_phone_page_renders(): void
    {
        $this->actingAs($this->user())
            ->get('/verify/phone')
            ->assertOk();
    }

    public function test_send_otp_updates_phone_and_stores_hashed_code(): void
    {
        $user = $this->user(['phone' => null]);
        $newPhone = '+2348098765432';

        $this->actingAs($user)
            ->post('/verify/phone', ['phone' => $newPhone])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame($newPhone, $user->fresh()->phone);

        $otp = PhoneOtp::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertNotSame('123456', $otp->token_hash);
        $this->assertNull($otp->consumed_at);
        $this->assertTrue($otp->isUsable());
    }

    public function test_send_otp_requires_a_phone(): void
    {
        $user = $this->user(['phone' => null]);

        $this->actingAs($user)
            ->post('/verify/phone', [])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('phone_otps', ['user_id' => $user->id]);
    }

    public function test_send_invalidates_earlier_codes(): void
    {
        $user = $this->user();

        $this->sendCode($user);
        $first = PhoneOtp::where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->travel(61)->seconds();
        $this->sendCode($user);

        $this->assertNotNull($first->fresh()->consumed_at);
    }

    public function test_verify_otp_marks_phone_verified(): void
    {
        $user = $this->user();
        $code = $this->sendCode($user);

        $this->actingAs($user)
            ->post('/verify/phone/verify', ['code' => $code])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->hasVerifiedPhone());
        $this->assertNotNull(PhoneOtp::where('user_id', $user->id)->latest('id')->firstOrFail()->consumed_at);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'phone_verified']);
    }

    public function test_wrong_code_increments_attempts_then_burns(): void
    {
        $user = $this->user();
        $code = $this->sendCode($user);
        $otp = PhoneOtp::where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->post('/verify/phone/verify', ['code' => '000001'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertFalse($user->fresh()->hasVerifiedPhone());

        config(['workride.phone_verification.otp_max_attempts' => 2]);
        $this->actingAs($user)
            ->post('/verify/phone/verify', ['code' => '000002'])
            ->assertSessionHasErrors('code');

        $this->actingAs($user)
            ->post('/verify/phone/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNotNull($otp->fresh()->consumed_at);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->user();
        $this->sendCode($user);

        $this->travel(11)->minutes();

        $this->actingAs($user)
            ->post('/verify/phone/verify', ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($user->fresh()->hasVerifiedPhone());
    }

    public function test_send_cooldown_blocks_rapid_requests(): void
    {
        $user = $this->user();
        $this->sendCode($user);

        $this->actingAs($user)
            ->post('/verify/phone', [])
            ->assertSessionHasErrors('phone');
    }

    public function test_daily_send_limit_blocks_after_five(): void
    {
        $user = $this->user();

        foreach (range(1, 5) as $i) {
            $this->sendCode($user);
            $this->travel(61)->seconds();
        }

        $this->actingAs($user)
            ->post('/verify/phone', [])
            ->assertSessionHasErrors('phone');

        $this->assertSame(5, PhoneOtp::where('user_id', $user->id)->count());
    }

    public function test_phone_verified_user_can_book_with_wallet(): void
    {
        $user = $this->user([
            'phone_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $user->id, 'cash_balance' => 2000]);

        $trip = $this->bookableTrip();

        $this->actingAs($user)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'trip_id' => $trip->id,
            'passenger_id' => $user->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_phone_only_user_cannot_use_subsidy_credits(): void
    {
        $user = $this->user([
            'phone_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $user->id, 'cash_balance' => 0, 'subsidy_credits' => 5000]);

        $trip = $this->bookableTrip();

        $this->actingAs($user)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'subsidy_credit'])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_phone_only_user_cannot_book_volunteer_rides(): void
    {
        $user = $this->user([
            'phone_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $user->id]);

        $trip = Trip::factory()->forDriver($this->driver())->volunteer()->create([
            'departure_time' => now()->addMinutes(30),
        ]);

        $this->actingAs($user)
            ->post("/trips/{$trip->id}/book", ['payment_method' => 'wallet'])
            ->assertSessionHasErrors('trip');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_phone_only_user_cannot_publish_trips(): void
    {
        $user = $this->user([
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post('/trips', [
                'corridor' => Corridor::KubwaCbd->value,
                'origin_text' => 'Kubwa',
                'destination_text' => 'CBD',
                'total_seats' => 4,
                'is_free_volunteer' => 1,
                'departure_time' => now()->addMinutes(60)->format('Y-m-d H:i'),
            ])
            ->assertForbidden();
    }
}
