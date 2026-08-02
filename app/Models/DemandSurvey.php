<?php

namespace App\Models;

use App\Enums\DemandDayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One manual junction count (BRT pre-design Method 1).
 */
class DemandSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'junction_id',
        'count',
        'destination_text',
        'hour',
        'day_type',
        'weather',
        'collected_by',
        'lat',
        'lng',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'hour' => 'integer',
            'day_type' => DemandDayType::class,
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function junction(): BelongsTo
    {
        return $this->belongsTo(Junction::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}
