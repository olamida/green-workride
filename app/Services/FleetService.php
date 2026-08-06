<?php

namespace App\Services;

use App\Enums\FaultStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\Fault;
use App\Models\Inspection;
use App\Models\MaintenanceSchedule;
use App\Models\Telemetry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Fleet lifecycle (guide §11): daily pre-trip inspections gate trip publishing,
 * faults open tickets, mileage drives preventive maintenance, telemetry keeps
 * OBD2 + phone readings. Starts asset-light — leased assets, not owned buses.
 */
class FleetService
{
    /**
     * Trip-publish gate. Applies only when a fleet asset is involved: either
     * explicitly supplied, or the driver's single assigned active asset.
     */
    public function assertPublishable(User $driver, ?int $assetId = null): ?Asset
    {
        $asset = $this->resolveAsset($driver, $assetId);

        if (! $asset) {
            return null;
        }

        if (! $asset->isServiceable()) {
            throw ValidationException::withMessages(['asset' => 'The assigned bus is not serviceable. Ground it in the Control Tower before publishing.']);
        }

        // The most recent inspection today decides. A failed inspection blocks
        // publishing until a later passing inspection clears it (latest wins).
        $latest = $asset->inspections()->whereDate('date', today())->latest('id')->first();

        if ($latest && ! $latest->is_passed) {
            throw ValidationException::withMessages(['asset' => 'This bus failed its pre-trip inspection today. Complete a passing inspection before publishing.']);
        }

        return $asset;
    }

    /**
     * Daily pre-trip inspection. A failed inspection blocks trip publishing
     * for that asset until it passes (or the fault is resolved + re-inspected).
     */
    public function recordInspection(User $driver, Asset $asset, array $data): Inspection
    {
        $inspection = Inspection::create([
            'asset_id' => $asset->id,
            'driver_id' => $driver->id,
            'date' => $data['date'] ?? today(),
            'tyre_photo_path' => $data['tyre_photo_path'] ?? null,
            'oil_level' => $data['oil_level'] ?? null,
            'interior_photo_path' => $data['interior_photo_path'] ?? null,
            'is_passed' => (bool) ($data['is_passed'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);

        if (! $inspection->is_passed) {
            $this->recordFault($driver, $asset, [
                'description' => 'Failed pre-trip inspection: '.($data['notes'] ?? 'unlisted issue.'),
                'severity' => 3,
            ]);
        }

        return $inspection;
    }

    public function recordFault(User $reporter, Asset $asset, array $data): Fault
    {
        return Fault::create([
            'asset_id' => $asset->id,
            'reported_by' => $reporter->id,
            'description' => $data['description'],
            'voice_note_path' => $data['voice_note_path'] ?? null,
            'severity' => (int) ($data['severity'] ?? 1),
            'status' => FaultStatus::Open,
        ]);
    }

    public function resolveFault(Fault $fault, int $resolvedBy, ?string $note = null): Fault
    {
        if ($fault->status === FaultStatus::Fixed) {
            throw ValidationException::withMessages(['fault' => 'Fault already resolved.']);
        }

        $fault->update([
            'status' => FaultStatus::Fixed,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_note' => $note ?? null,
        ]);

        return $fault;
    }

    /**
     * Create a maintenance schedule: preventive at 5,000 km from current
     * mileage (due in ~30 days), or a monthly inspection on the first of next
     * month. due_date is NOT NULL, so every job gets a target date.
     */
    public function scheduleMaintenance(Asset $asset, MaintenanceType $type, ?int $dueKm = null, ?string $notes = null): MaintenanceSchedule
    {
        $isPreventive = $type === MaintenanceType::Preventive5000km;

        return MaintenanceSchedule::create([
            'asset_id' => $asset->id,
            'type' => $type,
            'due_km' => $isPreventive ? ($dueKm ?? $asset->mileage + 5000) : null,
            'due_date' => $isPreventive ? today()->addDays(30) : now()->startOfMonth()->addMonth(),
            'status' => MaintenanceStatus::Scheduled,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark a maintenance item done; the asset is immediately serviceable again.
     */
    public function completeMaintenance(MaintenanceSchedule $schedule, ?string $notes = null): MaintenanceSchedule
    {
        $schedule->update([
            'status' => MaintenanceStatus::Done,
            'completed_at' => now(),
            'notes' => $notes ?? $schedule->notes,
        ]);

        return $schedule;
    }

    public function recordTelemetry(Asset $asset, array $data): Telemetry
    {
        $telemetry = Telemetry::create([
            'asset_id' => $asset->id,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'speed' => $data['speed'] ?? 0,
            'fuel_level' => $data['fuel_level'] ?? null,
            'engine_fault_code' => $data['engine_fault_code'] ?? null,
            'harsh_braking' => (bool) ($data['harsh_braking'] ?? false),
            'battery_soc' => $data['battery_soc'] ?? null,
            'range_km' => $data['range_km'] ?? null,
            'recorded_at' => now(),
        ]);

        if (($data['mileage'] ?? null) !== null) {
            $asset->update(['mileage' => max($asset->mileage, (int) $data['mileage'])]);

            $openPreventive = $asset->maintenanceSchedules()
                ->where('type', MaintenanceType::Preventive5000km->value)
                ->whereNotIn('status', [MaintenanceStatus::Done->value])
                ->latest('due_date')
                ->exists();

            if (! $openPreventive) {
                $this->scheduleMaintenance($asset, MaintenanceType::Preventive5000km, $asset->mileage + 5000);
            }
        }

        return $telemetry;
    }

    private function resolveAsset(User $driver, ?int $assetId): ?Asset
    {
        if ($assetId) {
            $asset = Asset::find($assetId);

            if (! $asset || $asset->assigned_driver_id !== $driver->id) {
                throw ValidationException::withMessages(['asset' => 'Asset not found for this driver.']);
            }

            return $asset;
        }

        $assigned = $driver->assets()->get();

        return $assigned->count() === 1 ? $assigned->first() : null;
    }
}
