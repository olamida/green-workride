<?php

namespace App\Enums;

enum ForecastEventType: string
{
    case Church = 'church';
    case Mosque = 'mosque';
    case Govt = 'govt';
    case Festive = 'festive';
    case Weather = 'weather';
    case FuelScarcity = 'fuel_scarcity';

    public function label(): string
    {
        return match ($this) {
            self::Church => 'Sunday / church',
            self::Mosque => 'Juma\'a / mosque',
            self::Govt => 'Government (FAAC / FEC / salary week)',
            self::Festive => 'Festive',
            self::Weather => 'Weather (rain)',
            self::FuelScarcity => 'Fuel scarcity',
        };
    }
}
