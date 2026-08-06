<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A charging station (WORKRIDE-DESIGN-REVIEWS §4): where an EV in the fleet can
 * charge. Location/geofence, power output and slots — the scheduler reads this
 * before deploying an EV on a corridor.
 */
class ChargingStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'kw',
        'slots',
        'is_available',
        'corridor',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'kw' => 'integer',
            'slots' => 'integer',
            'is_available' => 'boolean',
        ];
    }
}
