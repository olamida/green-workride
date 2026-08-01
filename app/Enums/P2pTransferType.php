<?php

namespace App\Enums;

enum P2pTransferType: string
{
    case Cash = 'cash';
    case Earned = 'earned';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Earned => 'Earned',
        };
    }
}
