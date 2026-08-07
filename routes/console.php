<?php

use App\Jobs\CalculateDemandForecastJob;
use App\Jobs\DeleteExpiredSelfiesJob;
use App\Jobs\GenerateRecurringTripsJob;
use App\Jobs\SendRideCreditRemindersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly: regenerate the GTFS static feed so Google's feed reflects new trips.
Schedule::command('gtfs:generate')->dailyAt('02:00');

// Nightly: purge encrypted selfies past the NDPR retention window.
Schedule::job(new DeleteExpiredSelfiesJob)->dailyAt('03:00');

// Nightly: retrain the demand forecast on bookings history (guide §9 Phase 2).
Schedule::job(new CalculateDemandForecastJob)->dailyAt('04:00');

// Nightly: materialise today + tomorrow's recurring schedule trips so the
// board/booking/GTFS machinery always has the guaranteed timetable ahead.
Schedule::job(new GenerateRecurringTripsJob)->dailyAt('05:00');

// Nightly: nudge ride-credit debtors before their due date so debt never
// silently ages to overdue (roadmap 3.4).
Schedule::job(new SendRideCreditRemindersJob(
    (int) config('workride.time_bank.remind_within_days', 3),
))->dailyAt('08:00');
