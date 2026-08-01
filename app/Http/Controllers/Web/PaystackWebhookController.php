<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WalletFundingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, WalletFundingService $funding): JsonResponse
    {
        $result = $funding->handlePaystackWebhook(
            (string) $request->getContent(),
            (string) $request->header('x-paystack-signature', ''),
        );

        return $result['ack']
            ? response()->json(['status' => 'ok', 'reason' => $result['reason']])
            : response()->json(['status' => 'invalid', 'reason' => $result['reason']], 400);
    }
}
