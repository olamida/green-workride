<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Models\Verification;
use App\Models\Wallet;
use App\Models\Workplace;
use App\Services\RoleSwitcherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private readonly RoleSwitcherService $roles) {}

    public function index()
    {
        $stats = [
            'users' => User::count(),
            'pending_verifications' => Verification::where('status', 'pending')->count(),
            'workplaces' => Workplace::count(),
            'trips_today' => Trip::whereDate('departure_time', today())->count(),
            'bookings' => Booking::count(),
            'subsidy_issued' => Wallet::sum('subsidy_credits'),
            'banned_users' => User::where('is_banned', true)->count(),
        ];

        $recentVerifications = Verification::with(['user', 'workplace'])
            ->latest()
            ->limit(8)
            ->get();

        $recentUsers = User::with('workplace')->latest()->limit(8)->get();

        $recentSos = ActivityLog::with('user')
            ->where('action', 'sos')
            ->latest()
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentVerifications', 'recentUsers', 'recentSos'));
    }

    public function viewAs(Request $request): RedirectResponse
    {
        $request->validate(['role' => ['required', 'string']]);

        $this->roles->switch($request->user(), $request->string('role'));

        return back()->with('status', 'Now viewing the Control Tower as '.$this->roles->effectiveRole($request->user())->label().'.');
    }

    public function resetViewAs(Request $request): RedirectResponse
    {
        $this->roles->reset($request->user());

        return back()->with('status', 'Back to admin view.');
    }
}
