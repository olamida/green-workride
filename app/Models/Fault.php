<?php

namespace App\Models;

use App\Enums\FaultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Driver-reported fault. Open faults ground the asset; resolving marks it fixed
 * and, for severe faults, the ops team schedules maintenance.
 */
class Fault extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'reported_by',
        'description',
        'voice_note_path',
        'severity',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => 'integer',
            'status' => FaultStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function markFixed(int $resolvedBy): void
    {
        $this->update([
            'status' => FaultStatus::Fixed,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);
    }
}
