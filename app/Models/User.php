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
        'avatar',
        'role',
        'verification_level',
        'workplace_id',
        'nin_hash',
        'nin_last4',
        'is_banned',
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

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isWorkplaceAdmin(): bool
    {
        return $this->role === UserRole::WorkplaceAdmin;
    }

    public function canBook(): bool
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
