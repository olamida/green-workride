<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Exceptions\VerificationThrottledException;
use App\Services\NimcVerificationService;
use App\Services\SmileIdService;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * The tiered KYC endpoints (Sprint 3.6) are feature-gated so a pilot can
     * turn them on per-MDA without touching the existing web/admin flow.
     */
    private function gate(): ?JsonResponse
    {
        if (! config('workride.verification.enabled')) {
            return response()->json(['message' => 'Tiered verification is not enabled.'], 403);
        }

        return null;
    }

    private function throttled(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (VerificationThrottledException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }
    }

    private function safeVerification($verification): array
    {
        return [
            'id' => $verification->id,
            'type' => $verification->type,
            'status' => $verification->status,
            'provider' => $verification->provider?->value,
            'tier' => $verification->tier?->value,
            'liveness_score' => $verification->liveness_score,
            'nin_last4' => $verification->nin_last4,
            'admin_note' => $verification->admin_note,
            'updated_at' => $verification->updated_at?->toIso8601String(),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'verifications' => $request->user()->verifications()->with('workplace')->get(),
        ]);
    }

    /**
     * My verification status — what the PWA shows on the /verify screen.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'verification_level' => $user->verification_level->value,
            'level_label' => $user->verification_level->label(),
            'verifications' => $user->verifications()->get()->map(fn ($v) => $this->safeVerification($v)),
        ]);
    }

    public function submitWorkplace(Request $request, VerificationService $service): JsonResponse
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

    public function submitNin(Request $request, VerificationService $service): JsonResponse
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

    /**
     * Tier 1 — staff ID via open liveness. Free, auto-approves on a pass.
     */
    public function tier1(Request $request, VerificationService $service): JsonResponse
    {
        if ($gate = $this->gate()) {
            return $gate;
        }

        $data = $request->validate([
            'workplace_id' => ['required', 'exists:workplaces,id'],
            'liveness_score' => ['required', 'integer', 'min:0', 'max:100'],
            'selfie_base64' => ['required', 'string'],
        ]);

        return $this->throttled(function () use ($request, $service, $data) {
            $user = $request->user();
            $service->assertWithinAttemptLimit($user, '1');

            $selfiePath = $service->storeSelfie($data['selfie_base64']);
            $verification = $service->submitTier1(
                $user,
                (int) $data['workplace_id'],
                (int) $data['liveness_score'],
                $selfiePath,
            );

            $service->recordAttempt($user, '1', $verification->provider->value, (int) $data['liveness_score'], $verification->status, $request->ip());

            return response()->json([
                'message' => 'Tier 1 verification processed.',
                'verification' => $this->safeVerification($verification),
            ], 201);
        });
    }

    /**
     * Tier 2 — NIN via a NIMC-licensed partner. The raw NIN is relayed only to
     * that partner; we store hash + last 4 + the partner's reference.
     */
    public function tier2(Request $request, VerificationService $service, NimcVerificationService $nimc): JsonResponse
    {
        if ($gate = $this->gate()) {
            return $gate;
        }

        $data = $request->validate([
            'nin' => ['required', 'digits:11'],
            'liveness_score' => ['required', 'integer', 'min:0', 'max:100'],
            'selfie_base64' => ['nullable', 'string'],
        ]);

        return $this->throttled(function () use ($request, $service, $nimc, $data) {
            $user = $request->user();
            $service->assertWithinAttemptLimit($user, '2');

            $selfiePath = isset($data['selfie_base64'])
                ? $service->storeSelfie($data['selfie_base64'])
                : null;

            $verification = $nimc->verify($user, $data['nin'], (int) $data['liveness_score'], $selfiePath);

            $service->recordAttempt($user, '2', $verification->provider->value, (int) $data['liveness_score'], $verification->status, $request->ip());

            return response()->json([
                'message' => 'NIN verification processed.',
                'verification' => $this->safeVerification($verification),
            ], 201);
        });
    }

    /**
     * Tier 3 — driver anti-spoof liveness (Smile Identity). Starts the flow;
     * the Smile webhook resolves the result.
     */
    public function tier3(Request $request, VerificationService $service, SmileIdService $smile): JsonResponse
    {
        if ($gate = $this->gate()) {
            return $gate;
        }

        $data = $request->validate([
            'id_card' => ['required', 'image', 'max:5120'],
            'selfie' => ['nullable', 'image', 'max:5120'],
        ]);

        return $this->throttled(function () use ($request, $service, $smile, $data) {
            $user = $request->user();
            $service->assertWithinAttemptLimit($user, '3');

            $idCardPath = $request->file('id_card')->store('verifications/driver', 'private');
            $selfiePath = isset($data['selfie'])
                ? $service->storeSelfieFile($request->file('selfie'))
                : null;

            $verification = $smile->start($user, $idCardPath, $selfiePath);

            $service->recordAttempt($user, '3', $verification->provider->value, null, $verification->status, $request->ip());

            return response()->json([
                'message' => 'Driver verification started. Complete the Smile SmartSelfie flow.',
                'verification' => $this->safeVerification($verification),
            ], 201);
        });
    }
}
