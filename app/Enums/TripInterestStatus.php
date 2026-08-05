<?php

namespace App\Enums;

enum TripInterestStatus: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Matched = 'matched';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Interested',
            self::Notified => 'Notified',
            self::Matched => 'Booked',
        };
    }
}
