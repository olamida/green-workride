<?php

namespace App\Jobs;

use App\Models\Verification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * NDPR retention schedule: purge encrypted selfies once their retention window
 * (default 30 days) has passed. Runs nightly — after review the biometric
 * artifact is gone; the verification record + liveness score remain.
 */
class DeleteExpiredSelfiesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Verification::query()
            ->whereNotNull('selfie_path')
            ->whereNotNull('selfie_retention_expires_at')
            ->where('selfie_retention_expires_at', '<', now())
            ->chunkById(100, function ($verifications) {
                foreach ($verifications as $verification) {
                    Storage::disk('private')->delete($verification->selfie_path);

                    $verification->update([
                        'selfie_path' => null,
                        'selfie_retention_expires_at' => null,
                    ]);
                }
            });
    }
}
