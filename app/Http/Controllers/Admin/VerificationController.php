<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $verifications = Verification::with(['user', 'workplace', 'reviewer'])
            ->when($request->query('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->query('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->latest()
            ->paginate(25);

        $counts = [
            'pending' => Verification::where('status', 'pending')->count(),
            'approved' => Verification::where('status', 'approved')->count(),
            'rejected' => Verification::where('status', 'rejected')->count(),
        ];

        return view('admin.verifications', compact('verifications', 'counts'));
    }

    public function approve(Request $request, Verification $verification, VerificationService $service)
    {
        $service->approve($verification, $request->user(), $request->input('note'));

        return back()->with('status', "Verification #{$verification->id} approved. User level updated.");
    }

    public function reject(Request $request, Verification $verification, VerificationService $service)
    {
        $note = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ])['note'];

        $service->reject($verification, $request->user(), $note);

        return back()->with('status', "Verification #{$verification->id} rejected.");
    }
}
