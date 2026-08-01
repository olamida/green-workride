<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Wallet = 'wallet';
    case Cash = 'cash';
    case SubsidyCredit = 'subsidy_credit';
    case Free = 'free';

    public function label(): string
    {
        return match ($this) {
            self::Wallet => 'Wallet',
            self::Cash => 'Cash',
            self::SubsidyCredit => 'Subsidy Credit',
            self::Free => 'Free (Volunteer)',
        };
    }
}
