<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Navigation-first search (guide §9B — "Where are you going?").
 *
 * Search resolves junctions/workplaces (with surveyed demand volume) and falls
 * back to free OSM geocoding; directions return route geometry + the trips
 * actually going that way + live demand; nearby returns the live map pins.
 * All read-only — no money, verification or booking gates are touched.
 */
class NavigationController extends Controller
{
    public function __construct(private NavigationService $navigation) {}

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:120'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;

        return response()->json([
            'data' => $this->navigation->search($data['q'], $lat, $lng),
        ]);
    }

    public function directions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_lat' => ['required', 'numeric', 'between:-90,90'],
            'from_lng' => ['required', 'numeric', 'between:-180,180'],
            'to_lat' => ['required', 'numeric', 'between:-90,90'],
            'to_lng' => ['required', 'numeric', 'between:-180,180'],
            'window_minutes' => ['nullable', 'integer', 'between:15,720'],
        ]);

        $result = $this->navigation->directions(
            ['lat' => (float) $data['from_lat'], 'lng' => (float) $data['from_lng']],
            ['lat' => (float) $data['to_lat'], 'lng' => (float) $data['to_lng']],
            isset($data['window_minutes']) ? (int) $data['window_minutes'] : null,
        );

        return response()->json(['data' => $result]);
    }

    public function nearby(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'between:0.5,10'],
        ]);

        return response()->json([
            'data' => $this->navigation->nearby(
                (float) $data['lat'],
                (float) $data['lng'],
                isset($data['radius']) ? (float) $data['radius'] : 2.0,
            ),
        ]);
    }
}
