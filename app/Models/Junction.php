<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A known waiting point (Berger, Banex, Kubwa Junction...). Surveyors count
 * people here; the rider check-in and probe data reference it for supply planning.
 */
class Junction extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'corridor',
        'lat',
        'lng',
        'zone',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(DemandSurvey::class);
    }

    /**
     * Total people counted at this junction (all recorded surveys).
     */
    public function totalCounted(): int
    {
        return (int) $this->surveys()->sum('count');
    }
}
