<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per paid external API call (Google Directions, Mapbox, …).
 * Open-source providers (OSRM self-hosted) log rows with cost 0 so we can
 * prove we prefer free infra — the "paid only as capped, logged fallback" rule.
 */
class ApiCostLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'service',
        'cost_naira',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'cost_naira' => 'decimal:2',
            'meta' => 'array',
        ];
    }
}
