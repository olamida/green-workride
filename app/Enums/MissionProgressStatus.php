<?php

namespace App\Enums;

enum MissionProgressStatus: string
{
    case InProgress = 'in_progress';
    case Achieved = 'achieved';
    case Awarded = 'awarded';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::Achieved => 'Goal reached — claiming…',
            self::Awarded => 'Reward paid out',
        };
    }
}
