<?php

namespace App\Enums;

enum EmployerProgramType: string
{
    case Full = 'full';
    case OneWay = 'one_way';
    case Percent = 'percent';
    case Capped = 'capped';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full coverage',
            self::OneWay => 'One-way only',
            self::Percent => 'Percentage',
            self::Capped => 'Per-trip capped',
        };
    }
}
