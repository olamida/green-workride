<?php

namespace App\Enums;

enum DriverPromptStatus: string
{
    case Prompted = 'prompted';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Prompted => 'Prompted',
            self::Accepted => 'Accepted',
            self::Dismissed => 'Dismissed',
        };
    }
}
