<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RewardAudience;
use App\Enums\RewardPeriod;
use App\Enums\RewardTrigger;
use App\Enums\RewardType;
use App\Http\Controllers\Controller;
use App\Models\RewardCampaign;
use App\Models\RewardClaim;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reward Campaign management (guide §2.2 stream #7 + §6 Workflow 2).
 *
 * Sponsors/operators create incentive campaigns; the engine auto-awards claims
 * on the configured trigger, and riders redeem their Green Points balance.
 */
class RewardController extends Controller
{
    public function index(Request $request)
    {
        $campaigns = RewardCampaign::withCount('claims')->latest()->get();

        $claims = RewardClaim::with(['campaign', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.rewards.index', compact('campaigns', 'claims'));
    }

    public function create()
    {
        return view('admin.rewards.create', [
            'triggers' => RewardTrigger::cases(),
            'types' => RewardType::cases(),
            'audiences' => RewardAudience::cases(),
            'periods' => RewardPeriod::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sponsor_name' => ['nullable', 'string', 'max:255'],
            'sponsor_type' => ['nullable', Rule::in(['company', 'government', 'ngo', 'foundation', 'cooperative'])],
            'trigger' => ['required', Rule::enum(RewardTrigger::class)],
            'period' => ['required', Rule::enum(RewardPeriod::class)],
            'type' => ['required', Rule::enum(RewardType::class)],
            'value' => ['required', 'numeric', 'min:1'],
            'audience' => ['nullable', Rule::enum(RewardAudience::class)],
            'budget_total' => ['nullable', 'numeric', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ]);

        RewardCampaign::create($data + [
            'reward_type' => $data['type'],
            'reward_value' => $data['value'],
            'created_by' => $request->user()->id,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()
            ->route('admin.rewards.index')
            ->with('status', 'Campaign created. The engine will award claims on the configured trigger.');
    }

    public function toggle(RewardCampaign $campaign)
    {
        $campaign->update(['active' => ! $campaign->active]);

        return back()->with('status', $campaign->active ? 'Campaign activated.' : 'Campaign paused.');
    }
}
