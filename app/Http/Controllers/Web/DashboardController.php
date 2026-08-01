<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $user->load(['wallet', 'impactStat', 'workplace', 'verifications']);

        return view('dashboard', compact('user'));
    }
}
