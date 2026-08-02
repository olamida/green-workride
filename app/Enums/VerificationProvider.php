<?php

namespace App\Enums;

/**
 * Which engine produced a verification result (Sprint 3.6 tiered KYC).
 * open = free in-browser liveness (Tier 1); identitypass/dojah = NIMC-licensed
 * NIN lookups (Tier 2); smile = commercial anti-spoof driver liveness (Tier 3).
 */
enum VerificationProvider: string
{
    case Open = 'open';
    case IdentityPass = 'identitypass';
    case Dojah = 'dojah';
    case Smile = 'smile';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open Source',
            self::IdentityPass => 'IdentityPass',
            self::Dojah => 'Dojah',
            self::Smile => 'Smile Identity',
        };
    }
}
