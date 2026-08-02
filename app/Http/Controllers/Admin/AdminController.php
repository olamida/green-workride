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

class AdminController extends Controller
{
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
}
