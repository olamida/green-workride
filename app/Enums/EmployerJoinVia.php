<?php

namespace App\Enums;

/**
 * How a staff member joined an employer program (guide §7 two enrollment
 * forms). 'self' = employee registered and requested to join, waiting for
 * admin confirmation. 'employer' = the organisation uploaded/created them.
 */
enum EmployerJoinVia: string
{
    case Self = 'self';
    case Employer = 'employer';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Self-serve request',
            self::Employer => 'Employer-uploaded',
        };
    }
}
