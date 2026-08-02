<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandRequest;
use App\Models\OdMatrix;
use App\Models\ProbeDemandPoint;
use App\Services\DemandService;

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

        return view('admin.ops.demand', compact('junctions', 'odMatrix', 'checkIns', 'probePoints', 'totals'));
    }
}
