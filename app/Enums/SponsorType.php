<?php

namespace App\Enums;

enum SponsorType: string
{
    case Government = 'government';
    case Private = 'private';
    case Community = 'community';

    public function label(): string
    {
        return match ($this) {
            self::Government => 'Government',
            self::Private => 'Private',
            self::Community => 'Community Trust',
        };
    }
}
