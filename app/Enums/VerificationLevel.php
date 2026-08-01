<?php

namespace App\Enums;

enum VerificationLevel: int
{
    case Unverified = 0;
    case WorkplaceVerified = 1;
    case NinVerified = 2;
    case DriverVerified = 3;

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::WorkplaceVerified => 'Workplace Verified',
            self::NinVerified => 'NIN Verified',
            self::DriverVerified => 'Driver Verified',
        };
    }

    public function canBook(): bool
    {
        return $this->value >= self::WorkplaceVerified->value;
    }

    public function canDrivePaid(): bool
    {
        return $this->value >= self::DriverVerified->value;
    }

    public function canDriveVolunteer(): bool
    {
        return $this->value >= self::WorkplaceVerified->value;
    }
}
