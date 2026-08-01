<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Wallet = 'wallet';
    case Cash = 'cash';
    case SubsidyCredit = 'subsidy_credit';
    case Paystack = 'paystack';
    case Free = 'free';
    case RideCredit = 'ride_credit';

    public function label(): string
    {
        return match ($this) {
            self::Wallet => 'Wallet',
            self::Cash => 'Cash',
            self::SubsidyCredit => 'Subsidy Credit',
            self::Paystack => 'Paystack',
            self::Free => 'Free (Volunteer)',
            self::RideCredit => 'Ride Credit',
        };
    }
}
