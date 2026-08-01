<?php

namespace App\Enums;

enum TransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Subsidy = 'subsidy';
    case Refund = 'refund';
    case Hold = 'hold';
    case Capture = 'capture';
    case TopUp = 'top_up';

    public function label(): string
    {
        return match ($this) {
            self::Credit => 'Credit',
            self::Debit => 'Debit',
            self::Subsidy => 'Subsidy Credit',
            self::Refund => 'Refund',
            self::Hold => 'Hold',
            self::Capture => 'Capture',
            self::TopUp => 'Wallet Top-up',
        };
    }
}
