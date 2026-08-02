<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TieredVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const SELFIE = 'iVBORw0KGgoAAAANSUhEUg==';

    private function enableLiveness(): void
    {
        config(['workride.verification.enabled' => true]);
    }

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    public function test_tier_endpoints_are_gated_off_by_default(): void
    {
        $workplace = Workplace::factory()->create();
        $this->actingUser();

        $this->postJson('/api/v1/verifications/tier1', [
            'workplace_id' => $workplace->id,
            'liveness_score' => 90,
            'selfie_base64' => self::SELFIE,
        ])->assertForbidden();

        $this->postJson('/api/v1/verifications/tier2', [
            'nin' => '12345678901',
            'liveness_score' => 90,
        ])->assertForbidden();
    }

    public function test_tier1_auto_approves_workplace_with_high_liveness(): void
    {
        $this->enableLiveness();
        Storage::fake('private');
        $workplace = Workplace::factory()->create();
        $user = $this->actingUser();

        $response = $this->postJson('/api/v1/verifications/tier1', [
            'workplace_id' => $workplace->id,
            'liveness_score' => 90,
            'selfie_base64' => self::SELFIE,
        ]);

        $response->assertCreated()
            ->assertJsonPath('verification.status', 'approved')
            ->assertJsonPath('verification.provider', 'open')
            ->assertJsonPath('verification.tier', '1');

        $verification = $user->verifications()->where('type', 'workplace_id')->first();
        $this->assertNotNull($verification);
        $this->assertEquals(90, $verification->liveness_score);
        $this->assertNotNull($verification->selfie_path);
        $this->assertNotNull($verification->selfie_retention_expires_at);

        // Selfie is stored encrypted on the private disk and decrypts back.
        Storage::disk('private')->assertExists($verification->selfie_path);
        $this->assertSame(base64_decode(self::SELFIE), $verification->decryptedSelfie());

        $this->assertSame(1, $user->refresh()->verification_level->value);
        $this->assertDatabaseHas('verification_attempts', [
            'user_id' => $user->id,
            'tier' => '1',
            'provider' => 'open',
            'status' => 'approved',
        ]);
    }

    public function test_tier1_low_liveness_drops_to_manual_review(): void
    {
        $this->enableLiveness();
        Storage::fake('private');
        $workplace = Workplace::factory()->create();
        $user = $this->actingUser();

        $this->postJson('/api/v1/verifications/tier1', [
            'workplace_id' => $workplace->id,
            'liveness_score' => 50,
            'selfie_base64' => self::SELFIE,
        ])->assertCreated()
            ->assertJsonPath('verification.status', 'pending_manual_review');

        $this->assertSame(0, $user->refresh()->verification_level->value);
    }

    public function test_tier1_rate_limits_after_two_attempts(): void
    {
        $this->enableLiveness();
        config(['workride.verification.attempts_per_day' => 2]);
        Storage::fake('private');
        $workplace = Workplace::factory()->create();
        $this->actingUser();

        $payload = [
            'workplace_id' => $workplace->id,
            'liveness_score' => 90,
            'selfie_base64' => self::SELFIE,
        ];

        $this->postJson('/api/v1/verifications/tier1', $payload)->assertCreated();
        $this->postJson('/api/v1/verifications/tier1', $payload)->assertCreated();
        $this->postJson('/api/v1/verifications/tier1', $payload)->assertStatus(429);
    }

    public function test_tier2_calls_identitypass_and_approves(): void
    {
        $this->enableLiveness();
        config(['services.identitypass.enabled' => true]);
        config(['services.identitypass.key' => 'test-key']);
        config(['services.identitypass.cost_naira' => 100]);
        Http::fake([
            'api.myidentitypass.com/*' => Http::response([
                'status' => true,
                'data' => ['nimc_ref' => 'NIMC-REF-001'],
            ]),
        ]);

        $user = $this->actingUser();
        $nin = '12345678901';

        $response = $this->postJson('/api/v1/verifications/tier2', [
            'nin' => $nin,
            'liveness_score' => 80,
        ]);

        $response->assertCreated()
            ->assertJsonPath('verification.status', 'approved')
            ->assertJsonPath('verification.provider', 'identitypass')
            ->assertJsonPath('verification.tier', '2');

        $verification = $user->verifications()->where('type', 'nin')->first();
        $this->assertNotNull($verification);
        $this->assertEquals(hash('sha256', $nin), $verification->document_hash);
        $this->assertEquals('8901', $verification->nin_last4);
        $this->assertEquals('NIMC-REF-001', $verification->nimc_reference);

        // Raw NIN is never stored, only the hash + last 4.
        $this->assertDatabaseMissing('verifications', ['nin_last4' => $nin]);
        $this->assertDatabaseMissing('verifications', ['document_hash' => $nin]);
        $this->assertDatabaseHas('verifications', ['document_hash' => hash('sha256', $nin)]);

        // Every commercial call is cost-logged with the audit trail.
        $this->assertDatabaseHas('api_cost_logs', [
            'provider' => 'identitypass',
            'service' => 'nin_check',
            'purpose' => 'nin_verification',
            'user_id' => $user->id,
            'cost_naira' => '100.00',
        ]);
        Http::assertSentCount(1);

        $this->assertSame(2, $user->refresh()->verification_level->value);
    }

    public function test_tier2_rejects_when_nin_not_found(): void
    {
        $this->enableLiveness();
        config(['services.identitypass.enabled' => true]);
        config(['services.identitypass.key' => 'test-key']);
        Http::fake([
            'api.myidentitypass.com/*' => Http::response(['status' => false]),
        ]);

        $user = $this->actingUser();

        $this->postJson('/api/v1/verifications/tier2', [
            'nin' => '12345678901',
            'liveness_score' => 80,
        ])->assertCreated()
            ->assertJsonPath('verification.status', 'rejected');

        $verification = $user->verifications()->where('type', 'nin')->first();
        $this->assertStringContainsString('not found', strtolower($verification->admin_note));
        $this->assertSame(0, $user->refresh()->verification_level->value);
    }

    public function test_tier2_unconfigured_falls_back_to_manual_review(): void
    {
        $this->enableLiveness();
        config(['services.identitypass.enabled' => false]);
        Http::fake();

        $user = $this->actingUser();

        $this->postJson('/api/v1/verifications/tier2', [
            'nin' => '12345678901',
            'liveness_score' => 80,
        ])->assertCreated()
            ->assertJsonPath('verification.status', 'pending_manual_review');

        Http::assertNothingSent();
        $this->assertSame(0, $user->refresh()->verification_level->value);
    }

    public function test_tier2_cap_reached_falls_back_to_manual_review_without_calling(): void
    {
        $this->enableLiveness();
        config(['services.identitypass.enabled' => true]);
        config(['services.identitypass.key' => 'test-key']);
        config(['services.identitypass.monthly_cap_naira' => 0]);
        Http::fake();

        $this->actingUser();

        $this->postJson('/api/v1/verifications/tier2', [
            'nin' => '12345678901',
            'liveness_score' => 80,
        ])->assertCreated()
            ->assertJsonPath('verification.status', 'pending_manual_review');

        Http::assertNothingSent();
    }

    public function test_tier2_same_nin_is_idempotent_no_second_paid_call(): void
    {
        $this->enableLiveness();
        config(['services.identitypass.enabled' => true]);
        config(['services.identitypass.key' => 'test-key']);
        Http::fake([
            'api.myidentitypass.com/*' => Http::response(['status' => true, 'data' => []]),
        ]);

        $this->actingUser();
        $nin = '12345678901';

        $this->postJson('/api/v1/verifications/tier2', ['nin' => $nin, 'liveness_score' => 80])->assertCreated();
        $this->postJson('/api/v1/verifications/tier2', ['nin' => $nin, 'liveness_score' => 80])->assertCreated();

        Http::assertSentCount(1);
    }

    public function test_tier3_starts_driver_verification(): void
    {
        $this->enableLiveness();
        config(['services.smile.enabled' => true]);
        Storage::fake('private');
        $user = $this->actingUser();

        $response = $this->postJson('/api/v1/verifications/tier3', [
            'id_card' => UploadedFile::fake()->image('driver-license.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('verification.status', 'pending')
            ->assertJsonPath('verification.provider', 'smile')
            ->assertJsonPath('verification.tier', '3');

        $verification = $user->verifications()->where('type', 'driver')->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->document_hash);
        $this->assertNotNull($verification->selfie_path);
        Storage::disk('private')->assertExists($verification->selfie_path);
    }

    public function test_smile_webhook_rejects_invalid_signature(): void
    {
        config(['services.smile.webhook_secret' => 'test-secret']);

        $this->postJson('/api/v1/webhooks/smile', ['user_id' => 1, 'result_code' => 0], [
            'x-smile-signature' => 'wrong',
        ])->assertStatus(400)->assertJsonPath('reason', 'invalid_signature');
    }

    public function test_smile_webhook_approves_driver_on_anti_spoof_pass(): void
    {
        config(['services.smile.webhook_secret' => 'test-secret']);
        config(['services.smile.anti_spoof_threshold' => 80]);
        config(['services.smile.cost_naira' => 400]);

        $user = User::factory()->create();
        $payload = json_encode([
            'user_id' => $user->id,
            'result_code' => 0,
            'anti_spoof_score' => 92,
        ]);
        $signature = hash_hmac('sha256', $payload, 'test-secret');

        $this->postJson('/api/v1/webhooks/smile', json_decode($payload, true), [
            'x-smile-signature' => $signature,
        ])->assertOk()->assertJsonPath('reason', 'approved');

        $verification = $user->verifications()->where('type', 'driver')->first();
        $this->assertNotNull($verification);
        $this->assertEquals('approved', $verification->status);
        $this->assertEquals(92, $verification->liveness_score);
        $this->assertEquals('smile', $verification->provider->value);
        $this->assertDatabaseHas('api_cost_logs', [
            'provider' => 'smile',
            'purpose' => 'driver_liveness',
            'user_id' => $user->id,
        ]);

        $this->assertSame(3, $user->refresh()->verification_level->value);
    }

    public function test_smile_webhook_rejects_low_anti_spoof_score(): void
    {
        config(['services.smile.webhook_secret' => 'test-secret']);
        config(['services.smile.anti_spoof_threshold' => 80]);

        $user = User::factory()->create();
        $payload = json_encode([
            'user_id' => $user->id,
            'result_code' => 0,
            'anti_spoof_score' => 40,
        ]);
        $signature = hash_hmac('sha256', $payload, 'test-secret');

        $this->postJson('/api/v1/webhooks/smile', json_decode($payload, true), [
            'x-smile-signature' => $signature,
        ])->assertOk()->assertJsonPath('reason', 'rejected');

        $this->assertSame(0, $user->refresh()->verification_level->value);
    }

    public function test_status_endpoint_reports_level_and_verifications(): void
    {
        $this->enableLiveness();
        $user = $this->actingUser();
        $user->update(['verification_level' => 1]);

        $this->getJson('/api/v1/verifications/status')
            ->assertOk()
            ->assertJsonPath('verification_level', 1)
            ->assertJsonPath('level_label', 'Workplace Verified');
    }
}
