<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Where probe cars crawl (speed < 5 km/h for > 2 min) = where people wait.
 * Automatic demand signal, aggregated from every WorkRide car.
 */
class ProbeDemandPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'lat',
        'lng',
        'corridor',
        'avg_speed',
        'dwell_time_seconds',
        'times_visited',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'avg_speed' => 'decimal:2',
            'dwell_time_seconds' => 'integer',
            'times_visited' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }
}
