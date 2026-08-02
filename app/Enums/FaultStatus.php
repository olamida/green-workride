<?php

namespace App\Enums;

enum FaultStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Fixed => 'Fixed',
        };
    }
}
