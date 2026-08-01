<?php

namespace App\Enums;

enum UserRole: string
{
    case Passenger = 'passenger';
    case Driver = 'driver';
    case Both = 'both';
    case Volunteer = 'volunteer';
    case WorkplaceAdmin = 'workplace_admin';
    case EmployerAdmin = 'employer_admin';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Passenger => 'Passenger',
            self::Driver => 'Driver',
            self::Both => 'Driver & Passenger',
            self::Volunteer => 'Volunteer',
            self::WorkplaceAdmin => 'Workplace Admin',
            self::EmployerAdmin => 'Employer Admin',
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

    public function isPassenger(): bool
    {
        return in_array($this, [self::Passenger, self::Both], true);
    }

    /**
     * Roles a user may self-select at registration.
     * Admin, WorkplaceAdmin and EmployerAdmin are assigned by an existing
     * admin (or the employer onboarding flow) only.
     */
    public static function assignableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => ! $case->isAdmin()
                && ! in_array($case, [self::WorkplaceAdmin, self::EmployerAdmin], true),
        ));
    }
}
