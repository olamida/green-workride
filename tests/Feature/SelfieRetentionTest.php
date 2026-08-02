<?php

namespace Tests\Feature;

use App\Jobs\DeleteExpiredSelfiesJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfieRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_selfie_is_purged_by_job(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();

        $verification = $user->verifications()->create([
            'type' => 'workplace_id',
            'status' => 'approved',
            'selfie_path' => 'selfies/old.enc',
            'selfie_retention_expires_at' => now()->subDay(),
        ]);
        Storage::disk('private')->put('selfies/old.enc', 'encrypted-bytes');

        (new DeleteExpiredSelfiesJob)->handle();

        Storage::disk('private')->assertMissing('selfies/old.enc');
        $this->assertNull($verification->refresh()->selfie_path);
        $this->assertNull($verification->selfie_retention_expires_at);
    }

    public function test_selfie_within_retention_window_is_kept(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();

        $verification = $user->verifications()->create([
            'type' => 'workplace_id',
            'status' => 'approved',
            'selfie_path' => 'selfies/fresh.enc',
            'selfie_retention_expires_at' => now()->addDays(10),
        ]);
        Storage::disk('private')->put('selfies/fresh.enc', 'encrypted-bytes');

        (new DeleteExpiredSelfiesJob)->handle();

        Storage::disk('private')->assertExists('selfies/fresh.enc');
        $this->assertNotNull($verification->refresh()->selfie_path);
    }
}
