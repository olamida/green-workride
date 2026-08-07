<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Requested = 'requested';
    case SoftHold = 'soft_hold';
    case Confirmed = 'confirmed';
    case Boarded = 'boarded';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::SoftHold => 'Seat held',
            self::Confirmed => 'Confirmed',
            self::Boarded => 'Boarded',
            self::Completed => 'Completed',
            self::NoShow => 'No Show',
            self::Cancelled => 'Cancelled',
        };
    }
}
