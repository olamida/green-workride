<?php

namespace App\Models;

use App\Enums\RideCreditStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trip_id',
        'booking_id',
        'seats_owed',
        'seats_repaid',
        'fare_value',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'seats_owed' => 'integer',
            'seats_repaid' => 'integer',
            'fare_value' => 'decimal:2',
            'due_date' => 'datetime',
            'status' => RideCreditStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function outstandingSeats(): int
    {
        return max(0, $this->seats_owed - $this->seats_repaid);
    }

    public function isSettled(): bool
    {
        return $this->status === RideCreditStatus::Repaid || $this->status === RideCreditStatus::Waived;
    }
}
