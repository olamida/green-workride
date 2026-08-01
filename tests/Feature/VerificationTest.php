<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workplace;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_guest_cannot_access_verification_page(): void
    {
        $this->get('/verify')->assertRedirect('/login');
    }

    public function test_user_can_submit_workplace_verification(): void
    {
        $workplace = Workplace::factory()->create();
        $user = $this->actingUser();

        $response = $this->post('/verify/workplace', [
            'workplace_id' => $workplace->id,
        ]);

        $response->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('verifications', [
            'user_id' => $user->id,
            'type' => 'workplace_id',
            'workplace_id' => $workplace->id,
            'status' => 'pending',
        ]);
    }

    public function test_workplace_verification_rejects_unknown_workplace(): void
    {
        $this->actingUser();

        $this->post('/verify/workplace', [
            'workplace_id' => 999999,
        ])->assertSessionHasErrors('workplace_id');
    }

    public function test_user_can_submit_nin_and_only_hash_is_stored(): void
    {
        $user = $this->actingUser();
        $rawNin = '12345678901';

        $this->post('/verify/nin', [
            'nin' => $rawNin,
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('verifications', [
            'user_id' => $user->id,
            'type' => 'nin',
            'nin_last4' => '8901',
        ]);

        // Raw NIN must never be stored anywhere.
        $this->assertDatabaseMissing('users', ['nin_last4' => $rawNin]);
        $this->assertDatabaseMissing('verifications', ['nin_last4' => $rawNin]);

        $verification = $user->verifications()->where('type', 'nin')->first();
        $this->assertEquals(64, strlen($verification->document_hash));
        $this->assertEquals(hash('sha256', $rawNin), $verification->document_hash);
    }

    public function test_nin_validation_requires_11_digits(): void
    {
        $this->actingUser();

        $this->post('/verify/nin', ['nin' => '12345'])
            ->assertSessionHasErrors('nin');
    }

    public function test_api_submits_workplace_and_nin(): void
    {
        $workplace = Workplace::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/v1/verifications/workplace', [
            'workplace_id' => $workplace->id,
        ])->assertCreated()->assertJsonPath('verification.type', 'workplace_id');

        $this->postJson('/api/v1/verifications/nin', [
            'nin' => '98765432109',
        ])->assertCreated()->assertJsonPath('verification.nin_last4', '2109');

        $this->assertDatabaseMissing('verifications', ['nin_last4' => '98765432109']);
    }

    public function test_verification_page_shows_submissions(): void
    {
        $workplace = Workplace::factory()->create();
        $user = $this->actingUser();

        $user->verifications()->create([
            'type' => 'workplace_id',
            'workplace_id' => $workplace->id,
            'status' => 'pending',
        ]);

        $this->get('/verify')
            ->assertOk()
            ->assertSee($workplace->name);
    }

    public function test_verification_service_hashes_nin_consistently(): void
    {
        $service = app(VerificationService::class);
        $hash = $service->hashNin(' 1234 5678 901 ');

        $this->assertEquals('8901', $hash['nin_last4']);
        $this->assertEquals(hash('sha256', '12345678901'), $hash['nin_hash']);
    }

    public function test_workplace_document_can_be_uploaded_and_hashed(): void
    {
        Storage::fake('public');
        $workplace = Workplace::factory()->create();
        $user = $this->actingUser();

        $file = UploadedFile::fake()->image('staff-id.jpg');

        $this->post('/verify/workplace', [
            'workplace_id' => $workplace->id,
            'document' => $file,
        ])->assertRedirect();

        $verification = $user->verifications()->where('type', 'workplace_id')->first();
        $this->assertNotNull($verification->document_hash);
        $this->assertEquals(64, strlen($verification->document_hash));
        $this->assertNotEmpty(Storage::disk('public')->allFiles('verifications'));
    }
}
