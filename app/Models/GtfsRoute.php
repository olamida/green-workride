<?php

namespace App\Models;

use Database\Factories\GtfsRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtfsRoute extends Model
{
    /** @use HasFactory<GtfsRouteFactory> */
    use HasFactory;

    protected $fillable = [
        'route_id',
        'agency_id',
        'route_short_name',
        'route_long_name',
        'route_type',
        'corridor',
        'city_id',
    ];

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
