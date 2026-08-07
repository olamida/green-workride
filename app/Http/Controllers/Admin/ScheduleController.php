<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BusScheduleStatus;
use App\Http\Controllers\Controller;
use App\Models\BusSchedule;
use App\Models\GtfsRoute;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Recurring supply Control Tower (guide §6 Workflow 5): declarative schedules
 * ("Kubwa→CBD every 15 min Mon–Fri 06:30–09:00") plus a manual
 * "materialise today/tomorrow" trigger for when Ops wants real trips now
 * instead of waiting for the nightly GenerateRecurringTripsJob.
 */
class ScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = BusSchedule::with(['route', 'vehicle', 'driver'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderBy('departure_time')
            ->get();

        return view('admin.schedules.index', compact('schedules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'route_id' => ['required', 'integer', 'exists:gtfs_routes,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'departure_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:departure_time'],
            'frequency_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'days_of_week' => ['required', 'array'],
            'days_of_week.*' => ['string', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'workplace_id' => ['nullable', 'integer', 'exists:workplaces,id'],
        ]);

        $schedule = BusSchedule::create($data);

        return redirect()
            ->route('admin.schedules.index')
            ->with('status', "Schedule created — {$schedule->routeLabel()} first departs {$schedule->departure_time}.");
    }

    public function toggle(Request $request, BusSchedule $schedule): RedirectResponse
    {
        $schedule->status = $schedule->isActive() ? BusScheduleStatus::Paused : BusScheduleStatus::Active;
        $schedule->save();

        return redirect()
            ->route('admin.schedules.index')
            ->with('status', $schedule->isActive() ? 'Schedule reactivated.' : 'Schedule paused — no new trips will be materialised.');
    }

    /**
     * Manually materialise today (and tomorrow when requested) so Ops never
     * waits for the nightly job.
     */
    public function materialize(Request $request, SchedulingService $scheduling): RedirectResponse
    {
        $createdToday = $scheduling->materializeDay(now());

        $createdTomorrow = $request->boolean('tomorrow')
            ? $scheduling->materializeDay(now()->addDay())
            : 0;

        return redirect()
            ->route('admin.schedules.index')
            ->with(
                'status',
                sprintf('Materialised %d trip(s) today, %d for tomorrow.', $createdToday, $createdTomorrow)
            );
    }

    public function destroy(BusSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')->with('status', 'Schedule deleted.');
    }

    public function create(): View
    {
        $routes = GtfsRoute::query()->orderBy('corridor')->get();
        $vehicles = Vehicle::with('owner')->where('papers_verified', true)->orderBy('plate_number')->get();
        $drivers = User::query()->whereIn('role', ['driver', 'both'])->orderBy('name')->get();

        return view('admin.schedules.create', compact('routes', 'vehicles', 'drivers'));
    }
}
