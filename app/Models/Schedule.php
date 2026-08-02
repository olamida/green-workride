<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An individual shift inside a duty roster.
 */
class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'duty_roster_id',
        'driver_id',
        'asset_id',
        'corridor',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => ScheduleStatus::class,
        ];
    }

    public function dutyRoster(): BelongsTo
    {
        return $this->belongsTo(DutyRoster::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
