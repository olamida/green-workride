<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Corridor;
use App\Enums\ForecastEventType;
use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Services\ForecastService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Demand calendar (guide §9): the ops planner logs known events (FAAC, Juma'a,
 * rain) and the forecast service suggests how many extra vehicles each
 * corridor needs so we never deploy empty buses.
 */
class ForecastController extends Controller
{
    public function index(ForecastService $forecasts)
    {
        $events = $forecasts->upcoming(30);

        return view('admin.forecasts.index', compact('events'));
    }

    public function store(Request $request, ForecastService $forecasts)
    {
        $data = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'event_type' => ['required', Rule::enum(ForecastEventType::class)],
            'event_name' => 'required|string|max:255',
            'corridor' => ['required', Rule::enum(Corridor::class)],
            'expected_demand_multiplier' => 'nullable|numeric|min:0.1|max:3',
            'notes' => 'nullable|string|max:1000',
        ]);

        $type = ForecastEventType::from($data['event_type']);
        $multiplier = isset($data['expected_demand_multiplier'])
            ? (float) $data['expected_demand_multiplier']
            : $forecasts->defaultMultiplier($type);

        $suggestion = $forecasts->suggest(
            Carbon::parse($data['date']),
            $data['corridor'],
            $multiplier
        );

        Forecast::create([
            'date' => $data['date'],
            'event_type' => $type,
            'event_name' => $data['event_name'],
            'corridor' => $data['corridor'],
            'expected_demand_multiplier' => $multiplier,
            'recommended_extra_vehicles' => $suggestion['recommended_extra_vehicles'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Event logged — auto-suggested '.$suggestion['recommended_extra_vehicles'].' extra vehicle(s).');
    }
}
