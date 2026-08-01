<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ImpactStat;
use App\Models\User;

class ImpactController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $user->load('impactStat');

        $personal = $user->impactStat
            ?: new ImpactStat(['total_trips' => 0, 'co2_saved_kg' => 0, 'fuel_saved_litres' => 0, 'trees_equivalent' => 0, 'level' => 1]);

        $leaderboard = User::query()
            ->whereHas('impactStat', fn ($q) => $q->where('co2_saved_kg', '>', 0))
            ->with('impactStat', 'workplace')
            ->orderByDesc(
                ImpactStat::select('co2_saved_kg')->whereColumn('impact_stats.user_id', 'users.id')
            )
            ->limit(25)
            ->get();

        $workplaceLeaderboard = $leaderboard
            ->filter(fn ($u) => $u->workplace_id === $user->workplace_id)
            ->take(10);

        return view('impact.index', [
            'personal' => $personal,
            'leaderboard' => $leaderboard,
            'workplaceLeaderboard' => $workplaceLeaderboard,
        ]);
    }
}
