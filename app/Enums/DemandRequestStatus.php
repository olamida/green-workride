<?php

namespace App\Enums;

enum DemandRequestStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Matched => 'Matched',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
        };
    }
}
