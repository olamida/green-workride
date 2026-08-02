<?php

namespace App\Enums;

/**
 * How a mission's reward is validated.
 * - auto:  the app measures progress from trusted events and pays out itself.
 * - proof: the user submits photo + location + note, the promoter reviews it,
 *          and the app pays out only after approval.
 */
enum MissionVerificationMode: string
{
    case Auto = 'auto';
    case Proof = 'proof';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Auto-verified by the app',
            self::Proof => 'Requires photo proof',
        };
    }
}
