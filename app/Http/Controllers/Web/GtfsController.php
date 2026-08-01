<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GtfsRtService;
use App\Services\GtfsService;

/**
 * Public GTFS endpoints — the static feed zip and the GTFS-realtime feeds that
 * Google Transit's partner service polls every ~30 seconds.
 */
class GtfsController extends Controller
{
    public function feed(GtfsService $gtfs)
    {
        $path = $gtfs->feedPath();

        if ($path === null) {
            abort(404, 'GTFS feed has not been generated yet.');
        }

        return response()->download($path, 'gtfs.zip', [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename=gtfs.zip',
        ]);
    }

    public function vehiclePositions(GtfsRtService $gtfsRt)
    {
        return response($gtfsRt->vehiclePositionsFeed(), 200, [
            'Content-Type' => 'application/x-protobuf',
        ]);
    }

    public function tripUpdates(GtfsRtService $gtfsRt)
    {
        return response($gtfsRt->tripUpdatesFeed(), 200, [
            'Content-Type' => 'application/x-protobuf',
        ]);
    }
}
