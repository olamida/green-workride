<?php

namespace App\Models;

use App\Enums\TripInterestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A passenger's soft "I want this journey" signal (section 2.2). Deliberately
 * NOT a booking: it holds no seat, touches no wallet, and preserves the unique
 * (trip_id, passenger_id) seat invariant. Upgrades to a real booking when the
 * passenger books (matched), and unlocks the "Find my bus" guide.
 */
class TripInterest extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'status',
        'registered_at',
        'notified_at',
        'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripInterestStatus::class,
            'registered_at' => 'datetime',
            'notified_at' => 'datetime',
            'matched_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
