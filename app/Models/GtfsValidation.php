<?php

namespace App\Models;

use App\Enums\GtfsValidationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Google Transit validation run of the generated feed (guide §12) — the
 * history that proves WorkRide owns Abuja's first publishable GTFS.
 */
class GtfsValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_path',
        'validator_version',
        'status',
        'errors_count',
        'warnings_count',
        'report_path',
        'generated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GtfsValidationStatus::class,
            'errors_count' => 'integer',
            'warnings_count' => 'integer',
            'validated_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
