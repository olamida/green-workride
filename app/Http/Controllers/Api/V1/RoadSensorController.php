<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoadEventType;
use App\Http\Controllers\Controller;
use App\Services\GeofenceService;
use App\Services\RoadIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoadSensorController extends Controller
{
    public function __construct(
        private RoadIntelligenceService $intelligence,
        private GeofenceService $geofence,
    ) {}

    /**
     * POST /api/v1/road-events
     *
     * Records one accelerometer/GPS sample from the driver's phone while a
     * trip is active. Events must be inside the FCT bounding box.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['required', Rule::enum(RoadEventType::class)],
            'severity' => ['nullable', 'integer', 'between:1,5'],
            'speed' => ['nullable', 'numeric', 'between:0,300'],
            'accelerometer_z' => ['nullable', 'numeric'],
            'road_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->geofence->isInsideFct((float) $data['lat'], (float) $data['lng'])) {
            return response()->json([
                'message' => 'Road events can only be collected inside the FCT.',
            ], 422);
        }

        $data['user_id'] = $request->user()->id;

        $event = $this->intelligence->recordEvent($data);

        return response()->json([
            'message' => 'Road event recorded.',
            'event' => [
                'id' => $event->id,
                'lat' => (float) $event->lat,
                'lng' => (float) $event->lng,
                'type' => $event->type->value,
                'severity' => $event->severity,
                'is_confirmed' => $event->is_confirmed,
            ],
        ], 201);
    }

    /**
     * GET /api/v1/road-events?confirmed=true
     *
     * Public, anonymised road data for the heatmap — never exposes the driver.
     */
    public function index(Request $request)
    {
        $hours = $request->integer('hours');

        $events = $this->intelligence->confirmedPotholes($hours ?: null)
            ->map(fn ($event) => [
                'lat' => (float) $event->lat,
                'lng' => (float) $event->lng,
                'type' => $event->type->value,
                'severity' => $event->severity,
                'road_name' => $event->road_name,
                'reported_at' => $event->created_at->toIso8601String(),
            ]);

        return response()->json(['events' => $events]);
    }
}
