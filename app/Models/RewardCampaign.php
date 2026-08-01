<?php

namespace App\Models;

use App\Enums\RewardAudience;
use App\Enums\RewardPeriod;
use App\Enums\RewardTrigger;
use App\Enums\RewardType;
use App\Enums\SponsorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sponsor-funded incentive: "complete 5 rides this week → ₦500".
 * Awarded automatically by RewardService when the trigger fires — never
 * manually, so the incentive ledger is auditable end-to-end.
 */
class RewardCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'audience',
        'trigger',
        'reward_type',
        'reward_value',
        'period',
        'budget_total',
        'budget_spent',
        'sponsor_type',
        'sponsor_name',
        'starts_at',
        'ends_at',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'audience' => RewardAudience::class,
            'trigger' => RewardTrigger::class,
            'reward_type' => RewardType::class,
            'period' => RewardPeriod::class,
            'reward_value' => 'decimal:2',
            'budget_total' => 'decimal:2',
            'budget_spent' => 'decimal:2',
            'sponsor_type' => SponsorType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function claims(): HasMany
    {
        return $this->hasMany(RewardClaim::class, 'campaign_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRunningNow(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        return ! $this->ends_at || $this->ends_at->isFuture();
    }

    public function hasBudget(): bool
    {
        if (! $this->budget_total) {
            return true;
        }

        return (float) $this->budget_spent < (float) $this->budget_total;
    }
}
