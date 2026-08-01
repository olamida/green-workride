<?php

namespace App\Enums;

enum Corridor: string
{
    case KubwaCbd = 'kubwa_cbd';
    case NyanyaIdu = 'nyanya_idu';
    case LugbeCbd = 'lugbe_cbd';

    public function label(): string
    {
        return match ($this) {
            self::KubwaCbd => 'Kubwa → CBD',
            self::NyanyaIdu => 'Nyanya → Idu',
            self::LugbeCbd => 'Lugbe → CBD',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::KubwaCbd => 'KUB-CBD',
            self::NyanyaIdu => 'NYY-IDU',
            self::LugbeCbd => 'LUG-CBD',
        };
    }
}
