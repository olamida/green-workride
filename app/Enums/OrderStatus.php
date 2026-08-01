<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'Placed',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
        };
    }
}
