<?php

namespace App\Enums;

enum AssetAcquisitionType: string
{
    case Lease = 'lease';
    case Owned = 'owned';
    case Donated = 'donated';

    public function label(): string
    {
        return match ($this) {
            self::Lease => 'Lease',
            self::Owned => 'Owned',
            self::Donated => 'Donated',
        };
    }
}
