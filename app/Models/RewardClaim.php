<?php

namespace App\Models;

use App\Enums\RewardTrigger;
use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'trigger',
        'reward_type',
        'reward_value',
        'reference',
        'period_key',
        'meta',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger' => RewardTrigger::class,
            'reward_type' => RewardType::class,
            'reward_value' => 'decimal:2',
            'meta' => 'array',
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(RewardCampaign::class, 'campaign_id');
    }
}
