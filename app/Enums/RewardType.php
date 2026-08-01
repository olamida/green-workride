<?php

namespace App\Enums;

enum RewardType: string
{
    case Cash = 'cash';
    case Earned = 'earned';
    case Subsidy = 'subsidy';
    case GreenPoints = 'green_points';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash bonus',
            self::Earned => 'Earnings bonus',
            self::Subsidy => 'Subsidy credits',
            self::GreenPoints => 'Green Points',
        };
    }
}
