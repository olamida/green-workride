<?php

namespace App\Models;

use App\Enums\OdSurveyMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Workplace OD survey response (guide §9B Method 3): home area, travel time,
 * fare paid, mode — the raw material for the OD matrix + routes.txt.
 */
class OdSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'workplace_id',
        'user_id',
        'home_area',
        'departure_time',
        'arrival_time',
        'fare_paid',
        'mode',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime',
            'arrival_time' => 'datetime',
            'fare_paid' => 'decimal:2',
            'mode' => OdSurveyMode::class,
        ];
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
