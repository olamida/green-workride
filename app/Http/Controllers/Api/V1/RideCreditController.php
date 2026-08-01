<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RideCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideCreditController extends Controller
{
    public function index(Request $request, RideCreditService $service): JsonResponse
    {
        $user = $request->user();

        $credits = $user->rideCredits()
            ->with('trip')
            ->latest()
            ->get()
            ->map(fn ($credit) => [
                'id' => $credit->id,
                'seats_owed' => $credit->seats_owed,
                'seats_repaid' => $credit->seats_repaid,
                'outstanding_seats' => $credit->outstandingSeats(),
                'fare_value' => (float) $credit->fare_value,
                'status' => $credit->status->value,
                'due_date' => $credit->due_date?->toIso8601String(),
                'trip' => $credit->trip ? [
                    'id' => $credit->trip->id,
                    'route_name' => $credit->trip->route_name,
                ] : null,
            ]);

        return response()->json([
            'time_bank_enabled' => $service->enabled(),
            'outstanding_seats' => $service->outstandingSeats($user),
            'ride_credits' => $credits,
        ]);
    }
}
