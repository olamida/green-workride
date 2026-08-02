<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FaultStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Fault;
use App\Models\Inspection;
use App\Models\MaintenanceSchedule;
use App\Services\FleetService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fleet Control Tower (guide §11): assets, daily pre-trip inspections, faults
 * and the preventive-maintenance cadence. The publish gate in FleetService
 * enforces "no inspection → no trip" and "open fault → grounded".
 */
class FleetController extends Controller
{
    public function index()
    {
        $assets = Asset::with(['assignedDriver', 'inspections'])
            ->withCount(['faults as open_faults' => fn ($q) => $q->whereIn('status', ['open', 'in_progress'])])
            ->orderBy('status')
            ->get();

        $openFaults = Fault::with(['asset', 'reporter'])
            ->whereIn('status', [FaultStatus::Open->value, FaultStatus::InProgress->value])
            ->latest()
            ->limit(15)
            ->get();

        $upcomingMaintenance = MaintenanceSchedule::with('asset')
            ->whereIn('status', [MaintenanceStatus::Scheduled->value, MaintenanceStatus::Due->value])
            ->orderBy('due_date')
            ->limit(15)
            ->get();

        return view('admin.fleet.index', compact('assets', 'openFaults', 'upcomingMaintenance'));
    }

    public function recordInspection(Request $request, Asset $asset, FleetService $fleet)
    {
        $data = $request->validate([
            'is_passed' => 'required|boolean',
            'oil_level' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $fleet->recordInspection($request->user(), $asset, $data);

        return back()->with('status', $data['is_passed'] ? 'Inspection recorded — asset is cleared to publish.' : 'Inspection failed — asset grounded until the fault is fixed.');
    }

    public function resolveFault(Request $request, Fault $fault, FleetService $fleet)
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        $fleet->resolveFault($fault, $request->user()->id, $request->string('note'));

        return back()->with('status', 'Fault resolved.');
    }

    public function scheduleMaintenance(Request $request, Asset $asset, FleetService $fleet)
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(MaintenanceType::class)],
            'notes' => 'nullable|string|max:500',
        ]);

        $fleet->scheduleMaintenance($asset, MaintenanceType::from($data['type']), null, $data['notes'] ?? null);

        return back()->with('status', 'Maintenance scheduled.');
    }

    public function completeMaintenance(Request $request, MaintenanceSchedule $schedule, FleetService $fleet)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        $fleet->completeMaintenance($schedule, $request->string('notes'));

        return back()->with('status', 'Maintenance completed.');
    }
}
