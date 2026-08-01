<?php

namespace App\Enums;

/**
 * How a booking was actually covered by an employer — recorded per booking
 * so the rider's receipt can explain exactly what their company paid.
 */
enum EmployerCoverageType: string
{
    case Full = 'full';
    case OneWay = 'one_way';
    case Percent = 'percent';
    case Capped = 'capped';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Employer covered',
            self::OneWay => 'Employer covered (one-way)',
            self::Percent => 'Employer covered (partial)',
            self::Capped => 'Employer covered (capped)',
        };
    }
}
