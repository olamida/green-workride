<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandRequest;
use App\Models\OdMatrix;
use App\Models\ProbeDemandPoint;
use App\Services\DemandService;
use App\Services\DriverPromptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Demand Research dashboard (guide §9B — BRT pre-design with phones): junction
 * counts, the OD matrix, pending rider check-ins and probe dwell points feed
 * the corridor scheduling before any consultant's report would exist.
 */
class OpsController extends Controller
{
    public function index(DemandService $demand)
    {
        $junctions = $demand->junctionCounts();
        $odMatrix = OdMatrix::query()->orderByDesc('period_start')->orderByDesc('count')->limit(25)->get();
        $checkIns = DemandRequest::with('user')
            ->where('status', 'pending')
            ->latest('requested_at')
            ->limit(20)
            ->get();
        $probePoints = ProbeDemandPoint::query()
            ->orderByDesc('last_seen_at')
            ->limit(20)
            ->get();

        $totals = [
            'people_counted' => collect($junctions)->sum('count'),
            'pending_checkins' => $demand->pendingRequests(),
            'od_rows' => OdMatrix::count(),
            'probe_points' => ProbeDemandPoint::count(),
        ];

        $promptEnabled = (bool) config('workride.driver_prompts.enabled', false);

        return view('admin.ops.demand', compact('junctions', 'odMatrix', 'checkIns', 'probePoints', 'totals', 'promptEnabled'));
    }

    /**
     * "Nudge 5 drivers" (gallery "service planning" Phase 3): for every
     * corridor where live demand outstrips supply, prompt up to prompt_limit
     * qualified drivers to publish. Idempotent — a driver already prompted on
     * this corridor today is never re-notified.
     */
    public function nudge(Request $request, DriverPromptService $prompts): RedirectResponse
    {
        $result = $prompts->nudgeAll();
        $prompted = collect($result['corridors'])->sum('prompted');

        return back()->with(
            'status',
            $prompted > 0
                ? "Nudged {$prompted} drivers across ".collect($result['corridors'])->where('prompted', '>', 0)->count().' corridor(s).'
                : 'No corridor triggered the demand threshold right now — no prompts sent.',
        );
    }
}
