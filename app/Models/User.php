<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'phone_verified_at',
        'gender',
        'prefers_women_only',
        'emergency_contact_name',
        'emergency_contact_phone',
        'avatar',
        'role',
        'verification_level',
        'workplace_id',
        'nin_hash',
        'nin_last4',
        'is_banned',
        'has_overdue_ride_credit',
        'green_points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'nin_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'verification_level' => VerificationLevel::class,
            'is_banned' => 'boolean',
            'has_overdue_ride_credit' => 'boolean',
            'green_points' => 'integer',
            'prefers_women_only' => 'boolean',
            'phone_verified_at' => 'datetime',
        ];
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function verificationAttempts(): HasMany
    {
        return $this->hasMany(VerificationAttempt::class);
    }

    public function phoneOtps(): HasMany
    {
        return $this->hasMany(PhoneOtp::class);
    }

    /**
     * @return HasMany<DeviceToken, $this>
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'passenger_id');
    }

    public function impactStat(): HasOne
    {
        return $this->hasOne(ImpactStat::class);
    }

    public function roadEvents(): HasMany
    {
        return $this->hasMany(RoadEvent::class);
    }

    public function rideCredits(): HasMany
    {
        return $this->hasMany(RideCredit::class);
    }

    public function receivedTransfers(): HasMany
    {
        return $this->hasMany(P2pTransfer::class, 'receiver_user_id');
    }

    public function employerMemberships(): HasMany
    {
        return $this->hasMany(EmployerMember::class);
    }

    public function rewardClaims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }

    public function commodityPositions(): HasMany
    {
        return $this->hasMany(CommodityPosition::class);
    }

    public function shopOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(RideRating::class, 'rater_id');
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(RideRating::class, 'ratee_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_driver_id');
    }

    public function driverScores(): HasMany
    {
        return $this->hasMany(DriverScore::class);
    }

    public function fuelAdvances(): HasMany
    {
        return $this->hasMany(FuelAdvance::class);
    }

    public function demandRequests(): HasMany
    {
        return $this->hasMany(DemandRequest::class);
    }

    /**
     * Average rating received (driver score per the guide), or null when no
     * ratings yet. Rendered as "★ 4.8" on trip cards and the trip detail page.
     */
    public function ratingSummary(): ?float
    {
        return $this->ratingsReceived()->avg('rating');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isWorkplaceAdmin(): bool
    {
        return $this->role === UserRole::WorkplaceAdmin;
    }

    /**
     * True once an SMS OTP has proven the phone number is live. This is the
     * Tier-0 entry gate: a phone-verified rider can book at the normal fixed
     * fare, but benefits (subsidy, employer coverage, ride credits, volunteer
     * rides, rewards) stay locked behind formal verification (Level 1+).
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function canBook(): bool
    {
        return ! $this->is_banned && ($this->hasVerifiedPhone() || $this->verification_level->canBook());
    }

    /**
     * Benefit-tier booking: Level 1 (workplace) or above. Phone-only riders
     * get instant entry but pay the normal fare — this flag separates the
     * paid corridor economy from the subsidised/volunteer economy.
     */
    public function canBookBenefits(): bool
    {
        return ! $this->is_banned && $this->verification_level->canBook();
    }

    public function canDrivePaid(): bool
    {
        return ! $this->is_banned && $this->verification_level->canDrivePaid();
    }

    public function canDriveVolunteer(): bool
    {
        return ! $this->is_banned && $this->verification_level->canDriveVolunteer();
    }

    public function verificationLevelLabel(): string
    {
        return $this->verification_level->label();
    }
}
