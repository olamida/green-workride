<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Services\GtfsService;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * GTFS publisher demo (guide §12): after the rich trips are in place, this
 * regenerates the static feed zip and GTFS-RT snapshot so /gtfs/gtfs.zip and
 * the admin GTFS dashboard show real scheduled/active trips from the demo
 * corridors.
 */
class RichGtfsSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichGtfsSeeder.');

            return;
        }

        $tripCount = Trip::query()->whereIn('status', ['scheduled', 'active'])->count();

        if ($tripCount === 0) {
            $this->command?->error('RichGtfsSeeder needs scheduled/active trips first.');

            return;
        }

        // The service regenerates routes + zip + metadata in one call.
        $stats = app(GtfsService::class)->generate();

        $this->command?->info(sprintf(
            'GTFS feed regenerated for rich demo: %d stops, %d routes, %d trips (%d KB).',
            $stats['stops'],
            $stats['routes'],
            $stats['trips'],
            (int) round($stats['size'] / 1024)
        ));
    }
}
