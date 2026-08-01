<?php

namespace App\Enums;

enum UserRole: string
{
    case Passenger = 'passenger';
    case Driver = 'driver';
    case Both = 'both';
    case Volunteer = 'volunteer';
    case WorkplaceAdmin = 'workplace_admin';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Passenger => 'Passenger',
            self::Driver => 'Driver',
            self::Both => 'Driver & Passenger',
            self::Volunteer => 'Volunteer',
            self::WorkplaceAdmin => 'Workplace Admin',
            self::Admin => 'Admin',
        };
    }

    public function isDriver(): bool
    {
        return in_array($this, [self::Driver, self::Both], true);
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Roles a user may self-select at registration.
     * Admin and WorkplaceAdmin are assigned by an existing admin only.
     */
    public static function assignableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => ! $case->isAdmin() && $case !== self::WorkplaceAdmin,
        ));
    }
}
