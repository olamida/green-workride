<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preventive maintenance job: 5,000 km service or monthly inspection.
 */
class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'type',
        'due_km',
        'due_date',
        'status',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaintenanceType::class,
            'status' => MaintenanceStatus::class,
            'due_km' => 'integer',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function markDone(?string $notes = null): void
    {
        $this->update([
            'status' => MaintenanceStatus::Done,
            'completed_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }
}
