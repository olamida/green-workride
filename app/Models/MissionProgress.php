<?php

namespace App\Models;

use App\Enums\MissionProgressStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One user's tracked progress toward one auto-verified mission.
 */
class MissionProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mission_id',
        'metric_count',
        'status',
        'achieved_at',
        'awarded_at',
        'reference',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => MissionProgressStatus::class,
            'metric_count' => 'integer',
            'achieved_at' => 'datetime',
            'awarded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [MissionProgressStatus::Achieved, MissionProgressStatus::Awarded], true);
    }
}
