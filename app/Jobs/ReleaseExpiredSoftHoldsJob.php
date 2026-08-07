<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Soft-reservation janitor (P3): every minute, release soft-held seats whose
 * `soft_hold_expires_at` has passed — refunding the wallet hold (idempotent
 * by reference), returning the seat to the trip and broadcasting the change
 * so the live board refreshes. A no-op when FEATURE_SOFT_HOLD is off.
 */
class ReleaseExpiredSoftHoldsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private ?int $limit = null) {}

    public function handle(BookingService $bookings): void
    {
        $bookings->releaseExpiredSoftHolds(
            $this->limit ?? (int) config('workride.soft_hold.release_batch_size', 50),
        );
    }
}
