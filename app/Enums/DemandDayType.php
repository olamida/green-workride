<?php

namespace App\Enums;

enum DemandDayType: string
{
    case Weekday = 'weekday';
    case Weekend = 'weekend';

    public function label(): string
    {
        return match ($this) {
            self::Weekday => 'Weekday',
            self::Weekend => 'Weekend',
        };
    }
}
