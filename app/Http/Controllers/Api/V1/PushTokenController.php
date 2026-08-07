<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PushPlatform;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Device-token registration for FCM push (roadmap P3.2).
 *
 * Clients (web PWA, future Android/iOS) call POST /push/tokens with their
 * FCM registration token after auth, and DELETE /push/tokens on logout or
 * when the user disables notifications. Feature-gated: when push is disabled
 * the endpoints answer 403 so clients degrade gracefully.
 */
class PushTokenController extends Controller
{
    public function store(Request $request, FcmService $fcm): JsonResponse
    {
        if (! config('workride.push.enabled')) {
            return response()->json(['message' => 'Push notifications are disabled.'], 403);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', Rule::enum(PushPlatform::class)],
        ]);

        $deviceToken = $fcm->register(
            $user,
            $data['token'],
            isset($data['platform']) ? PushPlatform::from($data['platform']) : PushPlatform::Web,
        );

        return response()->json(['device_token' => $deviceToken], 201);
    }

    public function destroy(Request $request, FcmService $fcm): JsonResponse
    {
        if (! config('workride.push.enabled')) {
            return response()->json(['message' => 'Push notifications are disabled.'], 403);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $fcm->unregister($user, $data['token']);

        return response()->json(['ok' => true]);
    }
}
