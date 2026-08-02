<?php

namespace App\Enums;

enum MissionSubmissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting promoter review',
            self::Approved => 'Approved — reward paid',
            self::Rejected => 'Rejected',
        };
    }
}
