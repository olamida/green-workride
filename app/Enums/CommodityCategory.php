<?php

namespace App\Enums;

enum CommodityCategory: string
{
    case PreciousMetal = 'precious_metal';
    case Agricultural = 'agricultural';
    case Fuel = 'fuel';

    public function label(): string
    {
        return match ($this) {
            self::PreciousMetal => 'Precious metal',
            self::Agricultural => 'Agricultural',
            self::Fuel => 'Fuel',
        };
    }
}
