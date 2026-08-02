<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Scheduled = 'scheduled';
    case Due = 'due';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Due => 'Due',
            self::InProgress => 'In progress',
            self::Done => 'Done',
        };
    }
}
