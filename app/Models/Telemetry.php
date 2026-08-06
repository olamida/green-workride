<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One telemetry sample from an OBD2 dongle or the phone-in-car sensor:
 * speed, fuel, engine fault code, harsh braking (guide §11).
 */
class Telemetry extends Model
{
    use HasFactory;

    protected $table = 'telemetry';

    protected $fillable = [
        'asset_id',
        'lat',
        'lng',
        'speed',
        'fuel_level',
        'engine_fault_code',
        'harsh_braking',
        'battery_soc',
        'range_km',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'speed' => 'decimal:2',
            'fuel_level' => 'decimal:2',
            'harsh_braking' => 'boolean',
            'battery_soc' => 'decimal:2',
            'range_km' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
