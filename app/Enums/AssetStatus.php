<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case InMaintenance = 'in_maintenance';
    case Grounded = 'grounded';
    case Disposed = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::InMaintenance => 'In maintenance',
            self::Grounded => 'Grounded',
            self::Disposed => 'Disposed',
        };
    }

    public function isServiceable(): bool
    {
        return $this === self::Active;
    }
}
