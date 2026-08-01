<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use App\Services\WalletFundingService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(WalletService $walletService)
    {
        $user = auth()->user();
        $wallet = $walletService->walletFor($user);
        $transactions = $wallet->transactions()->latest()->limit(25)->get();

        return view('wallet.index', compact('wallet', 'transactions'));
    }

    public function topUp(Request $request, PaystackService $paystack, WalletFundingService $funding)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:1000000'],
        ]);

        if (! $paystack->isConfigured()) {
            return back()->withErrors([
                'amount' => 'Paystack is not configured yet. Add PAYSTACK_PUBLIC_KEY, PAYSTACK_SECRET_KEY and PAYSTACK_WEBHOOK_SECRET to .env to enable top-ups.',
            ]);
        }

        $init = $paystack->initialize(
            $request->user()->email,
            (float) $data['amount'],
            $funding->referenceFor($request->user()),
        );

        if (! $init) {
            return back()->withErrors(['amount' => 'Paystack could not start the payment. Please try again.']);
        }

        return redirect()->away($init['authorization_url']);
    }
}
