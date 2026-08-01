<?php

namespace App\Enums;

/**
 * Which spendable wallet balance paid for a shop/commerce purchase.
 * Subsidy credits can never buy goods — they are ride-only (guide §14).
 */
enum OrderPaymentSource: string
{
    case Cash = 'cash';
    case Earned = 'earned';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash balance',
            self::Earned => 'Earnings balance',
        };
    }
}
