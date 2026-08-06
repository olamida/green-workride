<?php

namespace App\Models;

use App\Enums\Corridor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A learned demand prediction (guide §9 Phase 2): the ML job averages
 * boarded/completed bookings per corridor + weekday + hour over the last four
 * weeks, applies any event multiplier for that date, and stores the snapshot
 * so Ops can schedule vehicles ahead of demand.
 */
class DemandForecast extends Model
{
    use HasFactory;

    protected $table = 'demand_forecasts';

    protected $fillable = [
        'date',
        'hour',
        'corridor',
        'baseline',
        'multiplier',
        'predicted',
        'data_points',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hour' => 'integer',
            'corridor' => Corridor::class,
            'baseline' => 'decimal:2',
            'multiplier' => 'decimal:2',
            'predicted' => 'decimal:2',
            'data_points' => 'integer',
        ];
    }
}
