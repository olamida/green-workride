<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PayoutService;
use App\Services\PaystackService;
use App\Services\WalletFundingService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $walletService): JsonResponse
    {
        $wallet = $walletService->walletFor($request->user());

        return response()->json([
            'cash_balance' => (float) $wallet->cash_balance,
            'subsidy_credits' => (float) $wallet->subsidy_credits,
            'earned_balance' => (float) $wallet->earned_balance,
            'cash_collected_log' => (float) $wallet->cash_collected_log,
            'transactions' => $wallet->transactions()->latest()->limit(50)->get(),
        ]);
    }

    public function topUp(Request $request, PaystackService $paystack, WalletFundingService $funding): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:1000000'],
        ]);

        if (! $paystack->canInitialize()) {
            return response()->json([
                'message' => 'Paystack is not configured. Add PAYSTACK_SECRET_KEY to .env to enable top-ups.',
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

    public function withdraw(Request $request, PayoutService $payouts): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'string', 'max:20'],
        ]);

        try {
            $payout = $payouts->withdraw(
                $request->user(),
                (float) $data['amount'],
                $data['bank_code'],
                $data['account_number'],
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Withdrawal failed.', 'errors' => $e->errors()], 422);
        }

        return response()->json([
            'message' => 'Withdrawal submitted.',
            'payout' => [
                'reference' => $payout->reference,
                'amount' => (float) $payout->amount,
                'status' => $payout->status->value,
            ],
        ], 201);
    }
}
