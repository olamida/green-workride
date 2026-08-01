<?php

namespace App\Enums;

enum RoadEventType: string
{
    case Pothole = 'pothole';
    case Bump = 'bump';
    case Rough = 'rough';
    case Flood = 'flood';

    public function label(): string
    {
        return match ($this) {
            self::Pothole => 'Pothole',
            self::Bump => 'Bump',
            self::Rough => 'Rough Surface',
            self::Flood => 'Flood',
        };
    }
}
