<?php

namespace App\Http\Controllers\Web;

use App\Enums\Corridor;
use App\Http\Controllers\Controller;
use App\Models\TripTemplate;
use App\Services\TripTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Driver trip templates (guide §11 driver tooling): save a recurring commute
 * once and republish with one tap. Feature-gated on FEATURE_TRIP_TEMPLATES
 * (on by default) — the page shows an off-notice when disabled, and every
 * publish still routes through TripService::publish (fixed fares intact).
 */
class TripTemplateController extends Controller
{
    public function __construct(private TripTemplateService $templates) {}

    public function index(Request $request): View
    {
        $enabled = (bool) config('workride.trip_templates.enabled', false);

        $templates = $enabled
            ? $this->templates->forDriver($request->user())
            : collect();

        return view('templates.index', compact('enabled', 'templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertEnabled();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'corridor' => ['required', Rule::enum(Corridor::class)],
            'origin_text' => ['required', 'string', 'max:255'],
            'destination_text' => ['required', 'string', 'max:255'],
            'departure_time' => ['required', 'date_format:H:i'],
            'days' => ['sometimes', 'array'],
            'days.*' => ['string', Rule::in(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'total_seats' => ['required', 'integer', 'between:1,60'],
            'fare_per_seat' => ['nullable', 'numeric', 'min:0'],
            'is_free_volunteer' => ['sometimes', 'boolean'],
            'women_only' => ['sometimes', 'boolean'],
            'waypoints' => ['nullable', 'array'],
        ]);

        $template = $this->templates->store($request->user(), $data);

        return redirect()->route('templates.index')
            ->with('status', "Template '{$template->name}' saved — publish it any morning with one tap.");
    }

    public function publish(Request $request, TripTemplate $template): RedirectResponse
    {
        $this->assertEnabled();
        $this->templates->assertOwner($template, $request->user());

        try {
            $trip = $this->templates->publish($template);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('trips.show', $trip)
            ->with('status', "Trip published from '{$template->name}'. Passengers near {$trip->route_name} have been notified.");
    }

    public function publishWeek(Request $request, TripTemplate $template): RedirectResponse
    {
        $this->assertEnabled();
        $this->templates->assertOwner($template, $request->user());

        try {
            $count = $this->templates->publishWeek($template);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('templates.index')
            ->with('status', "Published {$count} trips for '{$template->name}' this week.");
    }

    public function destroy(Request $request, TripTemplate $template): RedirectResponse
    {
        $this->assertEnabled();

        $this->templates->destroy($template, $request->user());

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }

    private function assertEnabled(): void
    {
        abort_unless((bool) config('workride.trip_templates.enabled', false), 403, 'Trip templates are disabled.');
    }
}
