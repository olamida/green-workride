<?php

namespace App\Services;

use App\Enums\MissionActivityType;
use App\Enums\MissionProgressStatus;
use App\Enums\MissionStatus;
use App\Enums\MissionSubmissionStatus;
use App\Enums\MissionVerificationMode;
use App\Enums\RewardType;
use App\Models\ActivityLog;
use App\Models\Mission;
use App\Models\MissionProgress;
use App\Models\MissionSubmission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Promoted volunteer activities ("Missions").
 *
 * The promoter defines an activity, a reward, and how it's verified; the app
 * observes real events and pays out. Two verification paths:
 *
 *  - auto:  MissionService::record() is called from trusted event flows (trip
 *           completed, pothole confirmed…) and progress is counted against the
 *           mission's metric_goal. On reaching the goal the reward is credited
 *           automatically — never on trust, always with an idempotent ledger.
 *  - proof: the user submits photo + location + note, the promoter/admin
 *           reviews it, and only an approval credits the reward.
 *
 * Every payout writes a wallet transaction keyed by a unique reference and an
 * ActivityLog entry, so sponsors (MDA, FERMA, corporates) get a fully
 * auditable "we paid exactly what we promised, for exactly what happened".
 */
class MissionService
{
    public function __construct(private WalletService $wallet) {}

    /**
     * Live missions + the signed-in user's progress/submissions, for the rider
     * hub.
     *
     * @return Collection<int, array{mission: Mission, progress: ?MissionProgress, submissions: Collection}>
     */
    public function activeFor(User $user): Collection
    {
        $missions = Mission::query()
            ->where('status', MissionStatus::Published)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByDesc('created_at')
            ->get();

        $ids = $missions->pluck('id');

        $progress = MissionProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $ids)
            ->get()
            ->keyBy('mission_id');

        $submissions = MissionSubmission::query()
            ->where('user_id', $user->id)
            ->whereIn('mission_id', $ids)
            ->latest()
            ->get()
            ->groupBy('mission_id');

        return $missions->map(fn (Mission $mission) => [
            'mission' => $mission,
            'progress' => $progress->get($mission->id),
            'submissions' => $submissions->get($mission->id, collect()),
        ]);
    }

    /**
     * The user's settled (achieved/awarded) missions.
     */
    public function myAwards(User $user): Collection
    {
        return MissionProgress::query()
            ->with('mission')
            ->where('user_id', $user->id)
            ->whereIn('status', [MissionProgressStatus::Achieved, MissionProgressStatus::Awarded])
            ->latest('awarded_at')
            ->get();
    }

    /**
     * Count one qualifying event toward every matching auto-verified mission.
     * Returns the missions whose goal was just reached and the reward paid.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, Mission>
     */
    public function record(User $user, MissionActivityType $activity, array $context = []): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $missions = Mission::query()
            ->where('status', MissionStatus::Published)
            ->where('activity_type', $activity->value)
            ->where('verification_mode', MissionVerificationMode::Auto->value)
            ->get();

        $awarded = [];

        foreach ($missions as $mission) {
            if (! $mission->isLive() || ! $mission->hasBudget()) {
                continue;
            }

            DB::transaction(function () use ($mission, $user, $context, &$awarded) {
                $progress = MissionProgress::query()
                    ->where('user_id', $user->id)
                    ->where('mission_id', $mission->id)
                    ->lockForUpdate()
                    ->first();

                if (! $progress) {
                    $progress = MissionProgress::create([
                        'user_id' => $user->id,
                        'mission_id' => $mission->id,
                        'metric_count' => 0,
                        'status' => MissionProgressStatus::InProgress,
                    ]);
                }

                // Already paid (or claimed) — never double-award.
                if ($progress->status !== MissionProgressStatus::InProgress) {
                    return;
                }

                $count = $progress->metric_count + 1;

                if ($count >= $mission->metric_goal) {
                    $this->awardProgress($mission, $user, $progress, $count, $context);
                    $awarded[] = $mission;

                    return;
                }

                $progress->update(['metric_count' => $count]);
            });
        }

        return $awarded;
    }

    /**
     * Submit photo + location proof for a proof-verified mission.
     */
    public function submitProof(User $user, Mission $mission, array $data): MissionSubmission
    {
        if (! $this->enabled()) {
            throw ValidationException::withMessages(['mission' => 'Missions are not enabled yet.']);
        }

        if (! $mission->isLive()) {
            throw ValidationException::withMessages(['mission' => 'This mission is not open for submissions.']);
        }

        if ($mission->verification_mode !== MissionVerificationMode::Proof) {
            throw ValidationException::withMessages(['mission' => 'This mission is auto-verified — no proof needed.']);
        }

        $photo = $data['proof_photo'];
        $path = $photo->store('mission-proofs', 'public');

        return MissionSubmission::create([
            'user_id' => $user->id,
            'mission_id' => $mission->id,
            'proof_photo_path' => $path,
            'note' => $data['note'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'status' => MissionSubmissionStatus::Pending,
        ]);
    }

    /**
     * Promoter/admin review of a proof submission. Approving credits the
     * mission reward; rejecting just closes the row.
     */
    public function review(User $reviewer, MissionSubmission $submission, bool $approve): MissionSubmission
    {
        if (! $approve) {
            $submission->update([
                'status' => MissionSubmissionStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $submission->fresh();
        }

        DB::transaction(function () use ($reviewer, $submission) {
            $submission = MissionSubmission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if ($submission->status !== MissionSubmissionStatus::Pending) {
                return;
            }

            $mission = $submission->mission;

            if (! $mission->hasBudget()) {
                throw ValidationException::withMessages(['submission' => 'Mission budget exhausted.']);
            }

            $value = (float) $mission->reward_value;
            $reference = "MIS-PROOF-{$mission->id}-{$submission->id}";

            $this->creditReward($mission, $submission->user, $reference, [
                'mission_id' => $mission->id,
                'submission_id' => $submission->id,
            ]);

            $mission->increment('budget_spent', $value);

            $submission->update([
                'status' => MissionSubmissionStatus::Approved,
                'reward_awarded' => true,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });

        return $submission->fresh();
    }

    /**
     * Payout for a freshly-completed auto mission. The reference is the
     * idempotency key that makes double-claiming impossible.
     */
    private function awardProgress(Mission $mission, User $user, MissionProgress $progress, int $count, array $context): void
    {
        $value = (float) $mission->reward_value;
        $reference = "MIS-{$mission->id}-{$user->id}-{$progress->id}";

        $this->creditReward($mission, $user, $reference, array_merge($context, ['mission_id' => $mission->id]));

        $mission->increment('budget_spent', $value);

        $progress->update([
            'metric_count' => $count,
            'status' => MissionProgressStatus::Awarded,
            'achieved_at' => now(),
            'awarded_at' => now(),
            'reference' => $reference,
            'meta' => $context,
        ]);
    }

    /**
     * The actual credit — cash/earned/subsidy via the idempotent wallet, or
     * Green Points on the user. Shared by the auto + proof paths.
     */
    private function creditReward(Mission $mission, User $user, string $reference, array $meta = []): void
    {
        $value = (float) $mission->reward_value;

        match ($mission->reward_type) {
            RewardType::Cash => $this->wallet->creditCash($user, $value, $reference, "Mission — {$mission->name}", $meta),
            RewardType::Earned => $this->wallet->creditEarned($user, $value, $reference, "Mission — {$mission->name}", $meta),
            RewardType::Subsidy => $this->wallet->creditSubsidy($user, $value, $reference, "Mission — {$mission->name}", $meta),
            RewardType::GreenPoints => $user->increment('green_points', max((int) round($value), 0)),
        };

        ActivityLog::log(
            $user,
            'mission_awarded',
            Mission::class,
            $mission->id,
            ['mission' => $mission->name, 'type' => $mission->reward_type->value, 'value' => $value, 'reference' => $reference],
        );
    }

    private function enabled(): bool
    {
        return (bool) config('workride.missions.enabled', false);
    }
}
