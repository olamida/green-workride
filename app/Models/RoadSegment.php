<?php

namespace App\Models;

use App\Enums\RoadCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoadSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'road_name',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'avg_iri',
        'condition',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'start_lat' => 'decimal:7',
            'start_lng' => 'decimal:7',
            'end_lat' => 'decimal:7',
            'end_lng' => 'decimal:7',
            'avg_iri' => 'decimal:2',
            'last_updated' => 'datetime',
            'condition' => RoadCondition::class,
        ];
    }
}
