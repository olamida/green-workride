<?php

namespace App\Enums;

enum PermitType: string
{
    case Cooperative = 'cooperative';
    case CommercialVehicle = 'commercial_vehicle';
    case Insurance = 'insurance';
    case Safety = 'safety';

    public function label(): string
    {
        return match ($this) {
            self::Cooperative => 'Staff Mobility Cooperative',
            self::CommercialVehicle => 'Commercial vehicle (VIO/DRTS)',
            self::Insurance => 'Insurance (Leadway/Coron)',
            self::Safety => 'Safety certificate',
        };
    }
}
