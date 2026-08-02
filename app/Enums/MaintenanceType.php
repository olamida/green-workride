<?php

namespace App\Enums;

enum MaintenanceType: string
{
    case Preventive5000km = 'preventive_5000km';
    case MonthlyInspection = 'monthly_inspection';

    public function label(): string
    {
        return match ($this) {
            self::Preventive5000km => 'Preventive · 5,000 km',
            self::MonthlyInspection => 'Monthly inspection',
        };
    }
}
