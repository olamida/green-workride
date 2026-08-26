<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'center_lat',
        'center_lng',
        'bounds_min_lat',
        'bounds_max_lat',
        'bounds_min_lng',
        'bounds_max_lng',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'center_lat' => 'decimal:7',
            'center_lng' => 'decimal:7',
            'bounds_min_lat' => 'decimal:7',
            'bounds_max_lat' => 'decimal:7',
            'bounds_min_lng' => 'decimal:7',
            'bounds_max_lng' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<Workplace, $this>
     */
    public function workplaces(): HasMany
    {
        return $this->hasMany(Workplace::class);
    }

    /**
     * @return HasMany<GtfsStop, $this>
     */
    public function gtfsStops(): HasMany
    {
        return $this->hasMany(GtfsStop::class);
    }

    /**
     * @return HasMany<GtfsRoute, $this>
     */
    public function gtfsRoutes(): HasMany
    {
        return $this->hasMany(GtfsRoute::class);
    }
}
