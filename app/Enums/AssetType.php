<?php

namespace App\Enums;

enum AssetType: string
{
    case Bus = 'bus';
    case Car = 'car';
    case Obd2Device = 'obd2_device';

    public function label(): string
    {
        return match ($this) {
            self::Bus => 'Bus',
            self::Car => 'Car',
            self::Obd2Device => 'OBD2 device',
        };
    }
}
