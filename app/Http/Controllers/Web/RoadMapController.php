<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RoadSegment;
use App\Services\RoadIntelligenceService;

/**
 * Public Road Intelligence heatmap — confirmed potholes + segment condition,
 * rendered with Leaflet. Anonymised: only lat/lng/severity, never the driver.
 */
class RoadMapController extends Controller
{
    public function __invoke(RoadIntelligenceService $intelligence)
    {
        $events = $intelligence->confirmedPotholes(72);
        $segments = RoadSegment::query()
            ->orderBy('avg_iri', 'desc')
            ->limit(20)
            ->get();

        $eventPoints = $events->map(fn ($event) => [
            'lat' => (float) $event->lat,
            'lng' => (float) $event->lng,
            'type' => $event->type->value,
            'severity' => $event->severity,
            'road_name' => $event->road_name,
        ]);

        return view('road.map', [
            'events' => $events,
            'eventPoints' => $eventPoints,
            'segments' => $segments,
        ]);
    }
}
