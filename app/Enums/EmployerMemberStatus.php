<?php

namespace App\Enums;

enum EmployerMemberStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Pending => 'Pending approval',
            self::Rejected => 'Rejected',
        };
    }
}
