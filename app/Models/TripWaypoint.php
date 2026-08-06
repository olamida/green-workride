<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripWaypoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'label',
        'lat',
        'lng',
        'sequence',
        'eta_minutes',
        'is_major_hub',
        'distance_from_origin_km',
        'geofence_radius_m',
        'reached_at',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'sequence' => 'integer',
            'eta_minutes' => 'integer',
            'is_major_hub' => 'boolean',
            'distance_from_origin_km' => 'decimal:2',
            'geofence_radius_m' => 'integer',
            'reached_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
