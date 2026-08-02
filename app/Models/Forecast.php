<?php

namespace App\Models;

use App\Enums\ForecastEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A known demand event (guide §9): FAAC week, Juma'a, rain, festivals. The
 * Control Tower demand calendar uses these to avoid deploying empty buses.
 */
class Forecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'event_type',
        'event_name',
        'corridor',
        'expected_demand_multiplier',
        'recommended_extra_vehicles',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'event_type' => ForecastEventType::class,
            'expected_demand_multiplier' => 'decimal:2',
            'recommended_extra_vehicles' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isUpcoming(): bool
    {
        return ! $this->date->isPast();
    }
}
