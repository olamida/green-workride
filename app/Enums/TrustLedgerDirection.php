<?php

namespace App\Enums;

enum TrustLedgerDirection: string
{
    // The Trust extends value (e.g. funds a ride-credit float).
    case Credit = 'credit';

    // The Trust receives value / releases a liability.
    case Debit = 'debit';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
        };
    }
}
