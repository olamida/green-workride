<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Corridor;
use App\Enums\DemandDayType;
use App\Http\Controllers\Controller;
use App\Services\DemandService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Demand research API (guide §9B) — the BRT pre-design field kit:
 *  - POST /demand/surveys   manual junction count (NYSC survey mode)
 *  - POST /demand/checkins  rider "I'm here, need a ride" signal
 *  - POST /demand/probes    slow-car dwell readings merged into points
 * All rows are anonymous-ish (collector id only) and feed the Ops demand map.
 */
class DemandController extends Controller
{
    public function surveys(Request $request, DemandService $demand)
    {
        $data = $request->validate([
            'junction_id' => 'required|exists:junctions,id',
            'count' => 'required|integer|min:0|max:1000',
            'destination_text' => 'nullable|string|max:255',
            'hour' => 'nullable|integer|between:0,23',
            'day_type' => ['nullable', Rule::enum(DemandDayType::class)],
            'weather' => 'nullable|string|max:50',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $survey = $demand->recordSurvey($request->user(), $data);

        return response()->json(['survey' => $survey], 201);
    }

    public function checkIns(Request $request, DemandService $demand)
    {
        $data = $request->validate([
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'destination_text' => 'required|string|max:255',
            'passengers_count' => 'required|integer|between:1,10',
        ]);

        $checkIn = $demand->checkIn($request->user(), $data);

        return response()->json(['request' => $checkIn], 201);
    }

    public function probes(Request $request, DemandService $demand)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'corridor' => ['nullable', Rule::enum(Corridor::class)],
            'avg_speed' => 'required|numeric|between:0,20',
            'dwell_time_seconds' => 'required|integer|between:60,3600',
        ]);

        $point = $demand->recordProbePoint($data);

        return response()->json(['point' => $point], 201);
    }
}
