<?php

namespace App\Enums;

enum MissionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Ended => 'Ended',
        };
    }
}
