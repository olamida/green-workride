<?php

namespace App\Enums;

enum EmployerTransactionType: string
{
    case Funding = 'funding';
    case Cover = 'cover';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Funding => 'Funding',
            self::Cover => 'Ride coverage',
            self::Refund => 'Coverage refund',
        };
    }
}
