<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\FleetService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * OBD2 + phone-in-car telemetry intake (guide §11). Only the driver assigned
 * to an asset may push samples; mileage updates auto-queue preventive
 * maintenance in FleetService.
 */
class FleetController extends Controller
{
    public function telemetry(Request $request, Asset $asset, FleetService $fleet)
    {
        if ($asset->assigned_driver_id !== $request->user()->id) {
            throw ValidationException::withMessages(['asset' => 'This asset is not assigned to you.']);
        }

        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'fuel_level' => ['nullable', 'numeric', 'between:0,100'],
            'engine_fault_code' => ['nullable', 'string', 'max:20'],
            'harsh_braking' => ['nullable', 'boolean'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'battery_soc' => ['nullable', 'numeric', 'between:0,100'],
            'range_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $telemetry = $fleet->recordTelemetry($asset, $data);

        return response()->json([
            'telemetry' => $telemetry,
            'mileage' => $asset->fresh()->mileage,
        ], 201);
    }
}
