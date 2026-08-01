<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RewardCampaign;
use App\Models\RewardClaim;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Rider-facing Green Points + Reward Campaign hub (guide §6 Workflow 2 +
 * §8 Green Challenge). Redemption converts points to wallet cash.
 */
class RewardsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $greenPoints = (int) $user->green_points;
        $rate = (float) config('workride.rewards.green_points_naira_per_point', 5);
        $minRedeem = (int) config('workride.rewards.green_points_min_redeem', 50);

        $campaigns = RewardCampaign::where('active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->get();

        $claims = RewardClaim::with('campaign')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(15)
            ->get();

        $enabled = (bool) config('workride.rewards.enabled', false);

        return view('rewards.index', compact('greenPoints', 'rate', 'minRedeem', 'campaigns', 'claims', 'enabled'));
    }

    public function redeem(Request $request, RewardService $rewards)
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
        ]);

        $naira = $rewards->redeemGreenPoints($request->user(), (int) $data['points']);

        return back()->with('status', 'Redeemed '.number_format((int) $data['points']).' Green Points for ₦'.number_format($naira, 2).' in your wallet.');
    }
}
