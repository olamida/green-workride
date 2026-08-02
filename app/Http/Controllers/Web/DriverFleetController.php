<?php

namespace App\Http\Controllers\Web;

use App\Enums\FaultStatus;
use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Fault;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Services\FleetService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Driver-side fleet (guide §11 workflow): the daily pre-trip inspection
 * checklist + photos before any publish, one-tap fault reporting, and a live
 * view of the assigned bus. The FleetService publish gate is already enforced
 * in TripService::publish — this UI is how a driver clears it.
 */
class DriverFleetController extends Controller
{
    public function index(Request $request)
    {
        $enabled = (bool) config('workride.fleet.enabled', false);

        $assets = $enabled
            ? $request->user()->assets()->with(['faults', 'maintenanceSchedules'])->latest('id')->get()
            : collect();

        $todayInspections = $assets->mapWithKeys(function (Asset $asset) {
            $latest = $asset->inspections()->whereDate('date', today())->latest('id')->first();

            return [$asset->id => $latest];
        });

        $openFaults = $enabled
            ? Fault::with('asset')
                ->where('reported_by', $request->user()->id)
                ->whereIn('status', [FaultStatus::Open->value, FaultStatus::InProgress->value])
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $upcomingMaintenance = $enabled
            ? MaintenanceSchedule::with('asset')
                ->whereIn('asset_id', $assets->pluck('id'))
                ->whereIn('status', [MaintenanceStatus::Scheduled->value, MaintenanceStatus::Due->value])
                ->orderBy('due_date')
                ->limit(10)
                ->get()
            : collect();

        return view('fleet.index', compact('enabled', 'assets', 'todayInspections', 'openFaults', 'upcomingMaintenance'));
    }

    public function inspect(Request $request, Asset $asset, FleetService $fleet)
    {
        $this->authorizeAsset($asset, $request->user());

        $data = $request->validate([
            'is_passed' => ['required', Rule::in(['1', '0', 1, 0])],
            'oil_level' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'tyre_photo' => ['nullable', 'image', 'max:4096'],
            'interior_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $fleet->recordInspection($request->user(), $asset, [
            'date' => today(),
            'tyre_photo_path' => $data['tyre_photo'] ?? null ? $this->storePhoto($asset, $data['tyre_photo']) : null,
            'interior_photo_path' => $data['interior_photo'] ?? null ? $this->storePhoto($asset, $data['interior_photo']) : null,
            'oil_level' => $data['oil_level'] ?? null,
            'is_passed' => (bool) $data['is_passed'],
            'notes' => $data['notes'] ?? null,
        ]);

        $message = $data['is_passed']
            ? "Inspection passed — {$asset->plate_number} is cleared to publish."
            : "Inspection failed — {$asset->plate_number} is grounded until the fault is fixed.";

        return back()->with('status', $message);
    }

    public function storeFault(Request $request, Asset $asset, FleetService $fleet)
    {
        $this->authorizeAsset($asset, $request->user());

        $data = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
            'severity' => ['required', 'integer', 'between:1,5'],
        ]);

        $fleet->recordFault($request->user(), $asset, $data);

        return back()->with('status', 'Fault reported — Control Tower has been notified.');
    }

    private function authorizeAsset(Asset $asset, ?User $user): void
    {
        if (! $user || $asset->assigned_driver_id !== $user->id) {
            abort(403, 'This asset is not assigned to you.');
        }
    }

    private function storePhoto(Asset $asset, UploadedFile $file): string
    {
        return $file->store("inspections/{$asset->id}", 'public');
    }
}
