<?php

namespace App\Enums;

enum LeaseStatus: string
{
    case Active = 'active';
    case PaidOff = 'paid_off';
    case Defaulted = 'defaulted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PaidOff => 'Paid off',
            self::Defaulted => 'Defaulted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isSettled(): bool
    {
        return $this === self::PaidOff;
    }
}
