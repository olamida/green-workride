<?php

namespace App\Models;

use App\Enums\Corridor;
use App\Enums\DriverPromptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demand → supply nudge (gallery "service planning" Phase 3): one row per
 * driver per corridor per day when live demand outstrips supply. Idempotent
 * per (driver, day, corridor) via the unique reference so the rate limit of
 * "1 push per driver per day per corridor" is enforced by the schema itself.
 */
class DriverPrompt extends Model
{
    protected $fillable = [
        'driver_id',
        'corridor',
        'people_count',
        'time_band',
        'status',
        'reference',
        'notified_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'corridor' => Corridor::class,
            'people_count' => 'integer',
            'status' => DriverPromptStatus::class,
            'notified_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function accept(): void
    {
        if ($this->status === DriverPromptStatus::Accepted) {
            return;
        }

        $this->update([
            'status' => DriverPromptStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function dismiss(): void
    {
        if ($this->status === DriverPromptStatus::Dismissed) {
            return;
        }

        $this->update(['status' => DriverPromptStatus::Dismissed]);
    }
}
