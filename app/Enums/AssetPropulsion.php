<?php

namespace App\Enums;

enum AssetPropulsion: string
{
    case Ice = 'ice';
    case Hybrid = 'hybrid';
    case Ev = 'ev';

    public function label(): string
    {
        return match ($this) {
            self::Ice => 'ICE',
            self::Hybrid => 'Hybrid',
            self::Ev => 'Electric',
        };
    }

    public function isEv(): bool
    {
        return $this === self::Ev;
    }
}
