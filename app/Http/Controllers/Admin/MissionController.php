<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MissionActivityType;
use App\Enums\MissionStatus;
use App\Enums\MissionVerificationMode;
use App\Enums\RewardType;
use App\Enums\SponsorType;
use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Services\MissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Control Tower: promote volunteer/community activities.
 *
 * The promoter defines what counts, what the reward is, and how it's verified
 * (auto vs photo proof). The app observes real events and pays out — the admin
 * only reviews photo-proof submissions and keeps missions within budget.
 */
class MissionController extends Controller
{
    public function index()
    {
        $missions = Mission::withCount([
            'progress as participants_count',
            'submissions as submissions_count',
        ])->latest()->get();

        $pending = MissionSubmission::where('status', 'pending')->count();

        return view('admin.missions.index', compact('missions', 'pending'));
    }

    public function create()
    {
        return view('admin.missions.create', [
            'activities' => MissionActivityType::cases(),
            'modes' => MissionVerificationMode::cases(),
            'types' => RewardType::cases(),
            'sponsors' => SponsorType::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sponsor_type' => ['required', Rule::enum(SponsorType::class)],
            'sponsor_name' => ['nullable', 'string', 'max:255'],
            'activity_type' => ['required', Rule::enum(MissionActivityType::class)],
            'metric_goal' => ['required', 'integer', 'min:1'],
            'metric_window_days' => ['required', 'integer', 'min:1'],
            'reward_type' => ['required', Rule::enum(RewardType::class)],
            'reward_value' => ['required', 'numeric', 'min:1'],
            'verification_mode' => ['required', Rule::enum(MissionVerificationMode::class)],
            'proof_label' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'budget_total' => ['nullable', 'numeric', 'min:1'],
            'status' => ['nullable', Rule::enum(MissionStatus::class)],
        ]);

        Mission::create($data + [
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'created_by' => $request->user()->id,
            'status' => $data['status'] ?? MissionStatus::Draft,
        ]);

        return redirect()
            ->route('admin.missions.index')
            ->with('status', 'Mission created. Publish it and the app starts observing real events.');
    }

    public function show(Mission $mission)
    {
        $mission->loadCount('progress as participants_count');

        $submissions = MissionSubmission::with(['user', 'mission'])
            ->where('mission_id', $mission->id)
            ->latest()
            ->get();

        $participants = $mission->progress()
            ->with('user')
            ->orderByDesc('metric_count')
            ->limit(25)
            ->get();

        return view('admin.missions.show', compact('mission', 'submissions', 'participants'));
    }

    public function toggle(Mission $mission)
    {
        $mission->update([
            'status' => $mission->status === MissionStatus::Published ? MissionStatus::Draft : MissionStatus::Published,
        ]);

        $state = $mission->status === MissionStatus::Published ? 'published' : 'moved to draft';

        return back()->with('status', "Mission {$state}. ".
            ($mission->status === MissionStatus::Published
                ? 'The app is now observing qualifying events.'
                : 'It will no longer earn progress or rewards.'));
    }

    public function approveSubmission(MissionSubmission $submission, MissionService $missions)
    {
        $missions->review(request()->user(), $submission, true);

        return back()->with('status', 'Proof approved — reward credited to the member.');
    }

    public function rejectSubmission(MissionSubmission $submission, MissionService $missions)
    {
        $missions->review(request()->user(), $submission, false);

        return back()->with('status', 'Proof rejected. No reward was paid.');
    }
}
