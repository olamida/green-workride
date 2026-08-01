<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpactStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_trips',
        'co2_saved_kg',
        'fuel_saved_litres',
        'trees_equivalent',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'total_trips' => 'integer',
            'co2_saved_kg' => 'decimal:2',
            'fuel_saved_litres' => 'decimal:2',
            'trees_equivalent' => 'decimal:2',
            'level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
