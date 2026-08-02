<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SmileIdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Smile Identity result callback. Signature (HMAC-SHA256) is the only gate —
 * same contract as the Paystack webhook. Acknowledges as 200 so Smile stops
 * retrying, rejects with 400 when the signature or payload is invalid.
 */
class SmileWebhookController extends Controller
{
    public function handle(Request $request, SmileIdService $smile): JsonResponse
    {
        $result = $smile->handleWebhook(
            (string) $request->getContent(),
            (string) $request->header('x-smile-signature', ''),
        );

        return $result['ack']
            ? response()->json(['status' => 'ok', 'reason' => $result['reason']])
            : response()->json(['status' => 'invalid', 'reason' => $result['reason']], 400);
    }
}
