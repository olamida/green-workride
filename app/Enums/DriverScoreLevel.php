<?php

namespace App\Enums;

enum DriverScoreLevel: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';

    public function label(): string
    {
        return match ($this) {
            self::Bronze => 'Bronze',
            self::Silver => 'Silver',
            self::Gold => 'Gold',
            self::Platinum => 'Platinum',
        };
    }

    /**
     * Map a 0-100 driver score to a level band.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 90 => self::Platinum,
            $score >= 75 => self::Gold,
            $score >= 55 => self::Silver,
            default => self::Bronze,
        };
    }
}
