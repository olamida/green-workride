<?php

namespace App\Jobs;

use App\Services\GtfsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateGtfsFeedJob implements ShouldQueue
{
    use Queueable;

    public function handle(GtfsService $gtfs): void
    {
        $gtfs->generate();
    }
}
