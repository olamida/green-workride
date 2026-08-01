<?php

namespace App\Services;

use App\Enums\RewardAudience;
use App\Enums\RewardPeriod;
use App\Enums\RewardTrigger;
use App\Enums\RewardType;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\RewardCampaign;
use App\Models\RewardClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sponsor-funded incentive engine (guide §2.2 stream #7 + §8 Green Challenge).
 *
 * Campaigns pay out automatically when a RewardTrigger fires — never on
 * trust. Every payout is an idempotent RewardClaim (REW-{campaign}-{user}-
 * {periodKey}) plus a wallet transaction, so government/private sponsors get
 * a fully auditable incentive ledger.
 */
class RewardService
{
    public function __construct(private WalletService $wallet) {}

    /**
     * Evaluate active campaigns for the given trigger and issue any due rewards.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, RewardCampaign> campaigns actually awarded
     */
    public function award(User $user, RewardTrigger $trigger, array $context = []): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $campaigns = RewardCampaign::query()
            ->where('trigger', $trigger->value)
            ->where('active', true)
            ->get();

        $awarded = [];

        foreach ($campaigns as $campaign) {
            if (! $campaign->isRunningNow() || ! $campaign->hasBudget()) {
                continue;
            }

            if (! $this->audienceMatches($campaign, $user)) {
                continue;
            }

            $periodKey = $this->periodKey($campaign, $context);
            $reference = "REW-{$campaign->id}-{$user->id}-{$periodKey}";

            if (RewardClaim::where('reference', $reference)->exists()) {
                continue;
            }

            $this->issue($campaign, $user, $reference, $periodKey, $context);
            $awarded[] = $campaign;
        }

        return $awarded;
    }

    /**
     * Credit the guide's core green-points economy (volunteer rides etc.).
     */
    public function creditGreenPoints(User $user, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        $user->increment('green_points', $points);

        ActivityLog::log($user, 'green_points_credited', null, null, ['points' => $points]);
    }

    /**
     * Redeem Green Points for wallet cash at config('workride.rewards.*').
     * Sub-subsidy spend path — converted cash is the user's own, so it lands
     * in cash_balance (never subsidy_credits, which stay ride-only).
     */
    public function redeemGreenPoints(User $user, int $points): float
    {
        $min = (int) config('workride.rewards.green_points_min_redeem', 50);

        if ($points < $min) {
            throw ValidationException::withMessages(['points' => "Minimum redemption is {$min} Green Points."]);
        }

        $rate = (float) config('workride.rewards.green_points_naira_per_point', 5);
        $naira = round($points * $rate, 2);

        DB::transaction(function () use ($user, $points, $naira) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($user->green_points < $points) {
                throw ValidationException::withMessages(['points' => 'Not enough Green Points.']);
            }

            $user->decrement('green_points', $points);

            $reference = 'GP-'.$user->id.'-'.$points.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

            $this->wallet->creditCash(
                $user,
                $naira,
                $reference,
                'Green Points redemption',
                ['points' => $points],
            );
        });

        return $naira;
    }

    private function issue(RewardCampaign $campaign, User $user, string $reference, string $periodKey, array $context): void
    {
        $value = (float) $campaign->reward_value;

        match ($campaign->reward_type) {
            RewardType::Cash => $this->wallet->creditCash($user, $value, $reference, "Reward — {$campaign->name}", ['campaign_id' => $campaign->id]),
            RewardType::Earned => $this->wallet->creditEarned($user, $value, $reference, "Reward — {$campaign->name}", ['campaign_id' => $campaign->id]),
            RewardType::Subsidy => $this->wallet->creditSubsidy($user, $value, $reference, "Reward — {$campaign->name}", ['campaign_id' => $campaign->id]),
            RewardType::GreenPoints => $user->increment('green_points', max((int) round($value), 0)),
        };

        RewardClaim::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'trigger' => $campaign->trigger->value,
            'reward_type' => $campaign->reward_type->value,
            'reward_value' => $value,
            'reference' => $reference,
            'period_key' => $periodKey,
            'meta' => $context,
            'awarded_at' => now(),
        ]);

        $campaign->increment('budget_spent', $value);

        ActivityLog::log(
            $user,
            'reward_awarded',
            RewardCampaign::class,
            $campaign->id,
            ['campaign' => $campaign->name, 'type' => $campaign->reward_type->value, 'value' => $value],
        );
    }

    private function audienceMatches(RewardCampaign $campaign, User $user): bool
    {
        $role = $user->role;

        return match ($campaign->audience) {
            RewardAudience::Drivers => $role->isDriver(),
            RewardAudience::Passengers => $role->isPassenger(),
            RewardAudience::Volunteers => $role === UserRole::Volunteer,
            RewardAudience::Both => $role->isDriver() || $role->isPassenger(),
        };
    }

    /**
     * Uniqueness key per period: once/daily/weekly/monthly carry a date-based
     * key; unlimited carries an event key from the context so every distinct
     * action can earn once.
     */
    private function periodKey(RewardCampaign $campaign, array $context): string
    {
        return match ($campaign->period) {
            RewardPeriod::Once => 'once',
            RewardPeriod::Daily => now()->format('Ymd'),
            RewardPeriod::Weekly => now()->format('o-W'),
            RewardPeriod::Monthly => now()->format('Ym'),
            RewardPeriod::Unlimited => (string) ($context['event_key'] ?? Str::uuid()->toString()),
        };
    }

    private function enabled(): bool
    {
        return (bool) config('workride.rewards.enabled', false);
    }
}
