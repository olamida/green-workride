<?php

namespace App\Enums;

enum RideCreditStatus: string
{
    case Owed = 'owed';
    case Repaid = 'repaid';
    case Overdue = 'overdue';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Owed => 'Owed',
            self::Repaid => 'Repaid',
            self::Overdue => 'Overdue',
            self::Waived => 'Waived',
        };
    }
}
