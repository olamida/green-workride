<?php

namespace App\Models;

use App\Enums\DemandRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Crowdsourced demand check-in (guide §9B Method 5): "I'm at Berger, need a
 * ride to Secretariat, 2 people" — even with no driver yet, this is future
 * supply planning for Ops.
 */
class DemandRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pickup_lat',
        'pickup_lng',
        'destination_text',
        'passengers_count',
        'requested_at',
        'status',
        'matched_trip_id',
    ];

    protected function casts(): array
    {
        return [
            'pickup_lat' => 'decimal:7',
            'pickup_lng' => 'decimal:7',
            'passengers_count' => 'integer',
            'requested_at' => 'datetime',
            'status' => DemandRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedTrip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'matched_trip_id');
    }
}
