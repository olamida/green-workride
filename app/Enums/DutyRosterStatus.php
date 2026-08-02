<?php

namespace App\Enums;

enum DutyRosterStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Active => 'Active',
            self::Completed => 'Completed',
        };
    }
}
