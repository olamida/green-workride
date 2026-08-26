<?php

namespace App\Models;

use Database\Factories\WorkplaceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workplace extends Model
{
    /** @use HasFactory<WorkplaceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'acronym',
        'zone',
        'lat',
        'lng',
        'geofence_radius_m',
        'is_government',
        'country_id',
        'city_id',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'geofence_radius_m' => 'integer',
            'is_government' => 'boolean',
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
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Verification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }
}
