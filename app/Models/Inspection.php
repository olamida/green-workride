<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily pre-trip inspection: checklist + photos. A failed inspection grounds
 * the asset via the FleetService publish gate (guide §11 workflow).
 */
class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'driver_id',
        'date',
        'tyre_photo_path',
        'oil_level',
        'interior_photo_path',
        'is_passed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_passed' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
