<?php

namespace App\Http\Controllers\Web;

use App\Enums\P2pTransferType;
use App\Http\Controllers\Controller;
use App\Models\P2pTransfer;
use App\Services\P2pTransferService;
use App\Services\PayoutService;
use App\Services\PaystackService;
use App\Services\RideCreditService;
use App\Services\WalletFundingService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function index(WalletService $walletService, RideCreditService $rideCredits)
    {
        $user = auth()->user();
        $wallet = $walletService->walletFor($user);
        $transactions = $wallet->transactions()->latest()->limit(25)->get();
        $rideCredits = $user->rideCredits()->with('trip')->latest()->get();
        $outstandingSeats = $rideCredits->filter(fn ($credit) => $credit->status->value === 'owed')->sum(fn ($credit) => $credit->outstandingSeats());
        $transfers = P2pTransfer::whereIn('sender_wallet_id', [$wallet->id])->with('receiver')->latest()->limit(10)->get();
        $payouts = $wallet->payouts()->latest()->limit(10)->get();

        return view('wallet.index', compact(
            'wallet',
            'transactions',
            'rideCredits',
            'outstandingSeats',
            'transfers',
            'payouts',
        ));
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

    public function transfer(Request $request, P2pTransferService $transfers)
    {
        $data = $request->validate([
            'receiver_phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'type' => ['required', 'in:cash,earned'],
        ]);

        try {
            $transfers->transfer(
                $request->user(),
                $data['receiver_phone'],
                (float) $data['amount'],
                P2pTransferType::from($data['type']),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', 'Transfer sent successfully.');
    }

    public function withdraw(Request $request, PayoutService $payouts)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'string', 'max:20'],
        ]);

        try {
            $payouts->withdraw(
                $request->user(),
                (float) $data['amount'],
                $data['bank_code'],
                $data['account_number'],
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', 'Withdrawal submitted to your bank account.');
    }
}
