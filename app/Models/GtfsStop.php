<?php

namespace App\Models;

use Database\Factories\GtfsStopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GtfsStop extends Model
{
    /** @use HasFactory<GtfsStopFactory> */
    use HasFactory;

    protected $fillable = [
        'stop_id',
        'stop_name',
        'stop_lat',
        'stop_lon',
        'corridor',
        'zone',
        'city_id',
    ];

    protected function casts(): array
    {
        return [
            'stop_lat' => 'decimal:7',
            'stop_lon' => 'decimal:7',
        ];
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
