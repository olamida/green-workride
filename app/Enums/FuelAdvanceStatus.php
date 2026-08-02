<?php

namespace App\Enums;

enum FuelAdvanceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Repaid = 'repaid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
            self::Repaid => 'Repaid',
        };
    }
}
