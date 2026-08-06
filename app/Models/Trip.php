<?php

namespace App\Models;

use App\Enums\Corridor;
use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'asset_id',
        'route_name',
        'corridor',
        'origin_text',
        'destination_text',
        'share_code',
        'current_lat',
        'current_lng',
        'total_seats',
        'available_seats',
        'fare_per_seat',
        'is_free_volunteer',
        'women_only',
        'status',
        'departure_time',
        'waypoints',
    ];

    protected function casts(): array
    {
        return [
            'current_lat' => 'decimal:7',
            'current_lng' => 'decimal:7',
            'total_seats' => 'integer',
            'available_seats' => 'integer',
            'fare_per_seat' => 'decimal:2',
            'is_free_volunteer' => 'boolean',
            'women_only' => 'boolean',
            'corridor' => Corridor::class,
            'status' => TripStatus::class,
            'departure_time' => 'datetime',
            'waypoints' => 'array',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function waypoints(): HasMany
    {
        return $this->hasMany(TripWaypoint::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(TripInterest::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RideRating::class);
    }

    /**
     * Is the given user a participant on this trip (driver or booked passenger)?
     * Used for chat access and private channel authorization.
     */
    public function isParticipant(User $user): bool
    {
        if ($this->driver_id === $user->id) {
            return true;
        }

        return $this->bookings()
            ->where('passenger_id', $user->id)
            ->whereIn('status', ['confirmed', 'boarded', 'completed', 'no_show'])
            ->exists();
    }

    /**
     * Lazily mint the short public share code ("send this ride to a colleague").
     * Idempotent — a trip keeps the same code once generated.
     */
    public function ensureShareCode(): string
    {
        if ($this->share_code) {
            return $this->share_code;
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (static::where('share_code', $code)->exists());

        $this->share_code = $code;
        $this->save();

        return $code;
    }
}
