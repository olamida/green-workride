<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('verifications', 'workplace');

        return view('verification.index', compact('user'));
    }

    public function storeWorkplace(Request $request, VerificationService $verificationService)
    {
        $data = $request->validate([
            'workplace_id' => ['required', 'exists:workplaces,id'],
            'document' => ['nullable', 'image', 'max:4096'],
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('verifications', 'public');
        }

        $verificationService->submitWorkplace(
            $request->user(),
            (int) $data['workplace_id'],
            $documentPath,
        );

        return back()->with('status', 'Workplace verification submitted. An admin will review it shortly.');
    }

    public function storeNin(Request $request, VerificationService $verificationService)
    {
        $data = $request->validate([
            'nin' => ['required', 'digits:11'],
        ]);

        $verificationService->submitNin($request->user(), $data['nin']);

        return back()->with('status', 'NIN verification submitted. Your NIN is hashed (SHA-256) — never stored raw.');
    }

    public function phone(Request $request)
    {
        $user = $request->user();

        return view('verification.phone', compact('user'));
    }

    public function sendPhoneOtp(Request $request, PhoneVerificationService $phoneService)
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string'],
        ]);

        $phoneService->sendOtp($request->user(), $data['phone'] ?? null);

        return back()->with('status', 'Verification code sent to your phone. Check your inbox or message centre.');
    }

    public function verifyPhone(Request $request, PhoneVerificationService $phoneService)
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $phoneService->verifyOtp($request->user(), $data['code']);

        return back()->with('status', 'Phone verified — you can now book rides.');
    }
}
