<?php

namespace App\Enums;

/**
 * Which riders a reward campaign targets.
 */
enum RewardAudience: string
{
    case Drivers = 'drivers';
    case Passengers = 'passengers';
    case Volunteers = 'volunteers';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Drivers => 'Drivers',
            self::Passengers => 'Passengers',
            self::Volunteers => 'Volunteers',
            self::Both => 'Drivers & passengers',
        };
    }
}
