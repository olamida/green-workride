<?php

namespace App\Models;

use App\Enums\RoadEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lat',
        'lng',
        'type',
        'severity',
        'speed',
        'accelerometer_z',
        'is_confirmed',
        'road_name',
    ];

    // Mirror DB column defaults so freshly-created models hold the real values
    // in memory (the DB default is otherwise only applied on insert).
    protected $attributes = [
        'severity' => 1,
        'is_confirmed' => false,
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'severity' => 'integer',
            'speed' => 'decimal:2',
            'accelerometer_z' => 'decimal:2',
            'is_confirmed' => 'boolean',
            'type' => RoadEventType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
