<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

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

    private function verifiedWorker(): User
    {
        $user = User::factory()->create([
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        Wallet::create(['user_id' => $user->id]);

        return $user;
    }

    private function participantTrip(): array
    {
        $driver = $this->driver();
        $passenger = $this->verifiedWorker();

        $trip = Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(30),
        ]);

        app(BookingService::class)->book($trip, $passenger, ['payment_method' => 'cash']);

        return [$driver, $passenger, $trip];
    }

    public function test_is_participant_only_for_driver_and_booked_passengers(): void
    {
        [$driver, $passenger, $trip] = $this->participantTrip();
        $stranger = $this->verifiedWorker();

        $this->assertTrue($trip->fresh()->isParticipant($driver));
        $this->assertTrue($trip->fresh()->isParticipant($passenger));
        $this->assertFalse($trip->fresh()->isParticipant($stranger));
    }

    public function test_non_participant_cannot_view_messages(): void
    {
        [, , $trip] = $this->participantTrip();
        $stranger = $this->verifiedWorker();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/trips/{$trip->id}/messages")
            ->assertForbidden();
    }

    public function test_participant_can_view_messages(): void
    {
        [$driver, $passenger, $trip] = $this->participantTrip();

        $trip->chatMessages()->create([
            'sender_id' => $driver->id,
            'message' => 'Parking at Berger, 2 mins away.',
        ]);

        $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/trips/{$trip->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'Parking at Berger, 2 mins away.');
    }

    public function test_participant_can_send_message_via_web(): void
    {
        [$driver, $passenger, $trip] = $this->participantTrip();

        $this->actingAs($passenger)
            ->postJson("/trips/{$trip->id}/messages", ['message' => 'I am at the junction now.'])
            ->assertCreated()
            ->assertJsonPath('chat.message', 'I am at the junction now.');

        $this->assertDatabaseHas('chat_messages', [
            'trip_id' => $trip->id,
            'sender_id' => $passenger->id,
            'message' => 'I am at the junction now.',
        ]);
    }

    public function test_non_participant_cannot_send_message(): void
    {
        [, , $trip] = $this->participantTrip();
        $stranger = $this->verifiedWorker();

        $this->actingAs($stranger)
            ->postJson("/trips/{$trip->id}/messages", ['message' => 'spam'])
            ->assertForbidden();

        $this->assertDatabaseMissing('chat_messages', ['trip_id' => $trip->id]);
    }

    public function test_message_validation_requires_text(): void
    {
        [$driver, $passenger, $trip] = $this->participantTrip();

        $this->actingAs($passenger)
            ->postJson("/trips/{$trip->id}/messages", ['message' => ''])
            ->assertStatus(422);
    }

    public function test_api_unverified_user_cannot_use_chat(): void
    {
        $driver = $this->driver();
        $trip = Trip::factory()->forDriver($driver)->create([
            'corridor' => Corridor::KubwaCbd,
            'departure_time' => now()->addMinutes(30),
        ]);

        $unverified = User::factory()->create();

        $this->actingAs($unverified, 'sanctum')
            ->getJson("/api/v1/trips/{$trip->id}/messages")
            ->assertForbidden();

        $this->actingAs($unverified, 'sanctum')
            ->postJson("/api/v1/trips/{$trip->id}/messages", ['message' => 'spam'])
            ->assertForbidden();
    }
}
