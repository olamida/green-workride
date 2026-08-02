<?php

namespace App\Enums;

enum OdSurveyMode: string
{
    case Bus = 'bus';
    case Keke = 'keke';
    case Taxi = 'taxi';
    case PrivateCar = 'private_car';
    case Walk = 'walk';

    public function label(): string
    {
        return match ($this) {
            self::Bus => 'Bus',
            self::Keke => 'Keke',
            self::Taxi => 'Taxi',
            self::PrivateCar => 'Private car',
            self::Walk => 'Walk',
        };
    }
}
