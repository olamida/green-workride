<?php

namespace App\Enums;

enum VehicleType: string
{
    case Sedan = 'sedan';
    case Coaster = 'coaster';
    case StaffBus = 'staff_bus';
    case Danfo = 'danfo';

    public function label(): string
    {
        return match ($this) {
            self::Sedan => 'Sedan',
            self::Coaster => 'Coaster',
            self::StaffBus => 'Staff Bus',
            self::Danfo => 'Danfo',
        };
    }
}
