<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GtfsService;

/**
 * GTFS dashboard for the Ops Control Tower — feed status, download, regenerate.
 */
class GtfsController extends Controller
{
    public function index(GtfsService $gtfs)
    {
        $meta = $gtfs->metadata();
        $feedPath = $gtfs->feedPath();

        return view('admin.gtfs', [
            'meta' => $meta,
            'feedExists' => $feedPath !== null,
            'feedUrl' => route('gtfs.feed'),
        ]);
    }

    public function regenerate(GtfsService $gtfs)
    {
        $stats = $gtfs->generate();

        return back()->with('status', sprintf(
            'GTFS feed regenerated — %d stops, %d routes, %d trips (%.1f KB).',
            $stats['stops'],
            $stats['routes'],
            $stats['trips'],
            $stats['size'] / 1024,
        ));
    }
}
