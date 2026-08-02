<?php

namespace App\Enums;

/**
 * The KYC tier that created a verification (Sprint 3.6).
 * Tier 1 = staff ID (open, $0), Tier 2 = NIN (hybrid, licensed partner),
 * Tier 3 = driver (commercial anti-spoof). Maps 1:1 to verification levels.
 */
enum VerificationTier: string
{
    case Tier1 = '1';
    case Tier2 = '2';
    case Tier3 = '3';
}
