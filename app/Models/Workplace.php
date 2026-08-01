<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workplace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'acronym',
        'zone',
        'lat',
        'lng',
        'geofence_radius_m',
        'is_government',
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }
}
