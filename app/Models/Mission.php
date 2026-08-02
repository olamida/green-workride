<?php

namespace App\Models;

use App\Enums\MissionActivityType;
use App\Enums\MissionStatus;
use App\Enums\MissionVerificationMode;
use App\Enums\RewardType;
use App\Enums\SponsorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A promoted volunteer/community activity. The promoter defines the activity,
 * the reward and how it is verified; the app observes real events, tracks
 * progress, and pays the reward automatically (auto) or after review (proof).
 */
class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sponsor_type',
        'sponsor_name',
        'activity_type',
        'metric_goal',
        'metric_window_days',
        'reward_type',
        'reward_value',
        'verification_mode',
        'proof_label',
        'instructions',
        'starts_at',
        'ends_at',
        'budget_total',
        'budget_spent',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'activity_type' => MissionActivityType::class,
            'reward_type' => RewardType::class,
            'verification_mode' => MissionVerificationMode::class,
            'sponsor_type' => SponsorType::class,
            'status' => MissionStatus::class,
            'metric_goal' => 'integer',
            'metric_window_days' => 'integer',
            'reward_value' => 'decimal:2',
            'budget_total' => 'decimal:2',
            'budget_spent' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(MissionProgress::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(MissionSubmission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Published + currently within its active window + not out of budget.
     */
    public function isLive(): bool
    {
        if ($this->status !== MissionStatus::Published) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasBudget(): bool
    {
        if (! $this->budget_total) {
            return true;
        }

        return (float) $this->budget_spent < (float) $this->budget_total;
    }

    public function progressPercent(int $count): int
    {
        if ($this->metric_goal <= 0) {
            return 0;
        }

        return (int) round(min(($count / $this->metric_goal) * 100, 100));
    }
}
