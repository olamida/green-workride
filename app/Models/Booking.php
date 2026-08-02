<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\EmployerCoverageType;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'passenger_id',
        'pickup_lat',
        'pickup_lng',
        'status',
        'fare_paid',
        'employer_contribution',
        'employer_coverage',
        'employer_id',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'pickup_lat' => 'decimal:7',
            'pickup_lng' => 'decimal:7',
            'fare_paid' => 'decimal:2',
            'employer_contribution' => 'decimal:2',
            'employer_coverage' => EmployerCoverageType::class,
            'status' => BookingStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RideRating::class);
    }

    /**
     * The rating this user gave on this booking, if any — used to hide the
     * "rate your ride" form once it has been submitted.
     */
    public function ratingBy(int $userId): ?RideRating
    {
        return $this->ratings()->where('rater_id', $userId)->first();
    }

    /**
     * The portion of the fare the passenger owes personally — the fare minus
     * any employer contribution. Holds, captures and refunds act on this.
     */
    public function passengerHoldAmount(): float
    {
        return round(max((float) $this->fare_paid - (float) $this->employer_contribution, 0), 2);
    }
}
