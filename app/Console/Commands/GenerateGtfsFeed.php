<?php

namespace App\Console\Commands;

use App\Services\GtfsService;
use Illuminate\Console\Command;

class GenerateGtfsFeed extends Command
{
    protected $signature = 'gtfs:generate';

    protected $description = 'Regenerate the GTFS static feed zip from current trips and stop catalog';

    public function handle(GtfsService $gtfs): int
    {
        $stats = $gtfs->generate();

        $this->info(sprintf(
            'GTFS feed regenerated: %s (%.1f KB, %d stops, %d routes, %d trips, md5 %s)',
            $stats['path'],
            $stats['size'] / 1024,
            $stats['stops'],
            $stats['routes'],
            $stats['trips'],
            substr($stats['hash'], 0, 8),
        ));

        return self::SUCCESS;
    }
}
