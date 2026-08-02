<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiCostLog;
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
            ->when($request->query('provider'), function ($query, $provider) {
                $query->where('provider', $provider);
            })
            ->latest()
            ->paginate(25);

        $counts = [
            'pending' => Verification::where('status', 'pending')->count(),
            'pending_manual_review' => Verification::where('status', 'pending_manual_review')->count(),
            'approved' => Verification::where('status', 'approved')->count(),
            'rejected' => Verification::where('status', 'rejected')->count(),
        ];

        // KYC spend this month — every commercial call is logged with purpose.
        $costs = [
            'identitypass' => (float) ApiCostLog::where('provider', 'identitypass')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('cost_naira'),
            'smile' => (float) ApiCostLog::where('provider', 'smile')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('cost_naira'),
        ];

        return view('admin.verifications', compact('verifications', 'counts', 'costs'));
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
