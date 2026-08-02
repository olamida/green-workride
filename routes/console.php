<?php

use App\Jobs\DeleteExpiredSelfiesJob;
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
