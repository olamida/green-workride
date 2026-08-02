<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CalculateDriverScoresJob;
use App\Models\DriverScore;

/**
 * Driver scoreboard (guide §8 governance): the weekly 0-100 snapshot —
 * ratings, rides, punctuality, pothole reports, green points — ranked by
 * level. Run the weekly job manually here; it also runs on a schedule.
 */
class ScoreboardController extends Controller
{
    public function index()
    {
        $scores = DriverScore::with('user')
            ->latest('period_start')
            ->orderByDesc('score')
            ->limit(50)
            ->get();

        $latest = DriverScore::query()->max('period_start');

        return view('admin.scoreboard.index', compact('scores', 'latest'));
    }

    public function run()
    {
        $count = (new CalculateDriverScoresJob)->handle();

        return back()->with('status', "Scored {$count} active driver(s) for the week.");
    }
}
