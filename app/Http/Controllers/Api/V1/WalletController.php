<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use App\Services\WalletFundingService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $walletService): JsonResponse
    {
        $wallet = $walletService->walletFor($request->user());

        return response()->json([
            'cash_balance' => (float) $wallet->cash_balance,
            'subsidy_credits' => (float) $wallet->subsidy_credits,
            'cash_collected_log' => (float) $wallet->cash_collected_log,
            'transactions' => $wallet->transactions()->latest()->limit(50)->get(),
        ]);
    }

    public function topUp(Request $request, PaystackService $paystack, WalletFundingService $funding): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:1000000'],
        ]);

        if (! $paystack->isConfigured()) {
            return response()->json([
                'message' => 'Paystack is not configured. Top-ups are unavailable.',
            ], 503);
        }

        $reference = $funding->referenceFor($request->user());

        $init = $paystack->initialize($request->user()->email, (float) $data['amount'], $reference);

        if (! $init) {
            return response()->json([
                'message' => 'Paystack could not start the payment. Please try again.',
            ], 502);
        }

        return response()->json([
            'reference' => $reference,
            'authorization_url' => $init['authorization_url'],
        ]);
    }
}
