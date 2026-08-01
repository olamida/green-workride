<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        $user = auth()->user()->load('verifications', 'workplace');

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
}
