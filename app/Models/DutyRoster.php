<?php

namespace App\Models;

use App\Enums\DutyRosterStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Duty roster: which drivers + assets cover which corridors on a given day.
 */
class DutyRoster extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'corridor',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => DutyRosterStatus::class,
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
