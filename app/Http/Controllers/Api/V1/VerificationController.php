<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'verifications' => $request->user()->verifications()->with('workplace')->get(),
        ]);
    }

    public function submitWorkplace(Request $request, VerificationService $service)
    {
        $data = $request->validate([
            'workplace_id' => ['required', 'exists:workplaces,id'],
        ]);

        $verification = $service->submitWorkplace($request->user(), (int) $data['workplace_id']);

        return response()->json([
            'message' => 'Workplace verification submitted.',
            'verification' => $verification,
        ], 201);
    }

    public function submitNin(Request $request, VerificationService $service)
    {
        $data = $request->validate([
            'nin' => ['required', 'digits:11'],
        ]);

        $verification = $service->submitNin($request->user(), $data['nin']);

        return response()->json([
            'message' => 'NIN verification submitted. Stored as SHA-256 hash only.',
            'verification' => $verification->only(['id', 'type', 'status', 'nin_last4']),
        ], 201);
    }
}
