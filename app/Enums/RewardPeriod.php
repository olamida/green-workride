<?php

namespace App\Enums;

enum RewardPeriod: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Unlimited = 'unlimited';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Once',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
            self::Unlimited => 'Every time',
        };
    }
}
