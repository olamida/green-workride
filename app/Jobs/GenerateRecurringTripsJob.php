<?php

namespace App\Jobs;

use App\Services\SchedulingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Nightly supply materialisation: turn the declarative BusSchedule rows into
 * real Trip rows for today and tomorrow so the board/booking/GTFS machinery
 * always has the guaranteed timetable ahead. Idempotent — re-runs (e.g. the
 * manual Control Tower "materialise" button) never duplicate trips.
 */
class GenerateRecurringTripsJob implements ShouldQueue
{
    use Queueable;

    public function handle(SchedulingService $scheduling): void
    {
        $scheduling->materializeDay(now());
        $scheduling->materializeDay(now()->addDay());
    }
}
