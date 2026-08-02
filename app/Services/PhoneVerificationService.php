<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\PhoneOtp;
use App\Models\User;
use App\Notifications\SendPhoneOtp;
use Illuminate\Validation\ValidationException;

/**
 * Tier-0 phone verification — the "instant booking" gate.
 *
 * An OTP proves the phone number is live so a new rider can book at the
 * normal fixed fare before completing formal KYC. Codes are six digits,
 * stored as a SHA-256 hash, single-use, short-lived, rate-limited on both
 * send and verify so a cheap SIM cannot be farmed into the subsidised
 * economy (benefits stay locked behind Level 1+).
 */
class PhoneVerificationService
{
    private const PURPOSE = 'phone_verify';

    /**
     * Send a fresh OTP. Any earlier un-consumed code for this user is
     * invalidated so only the newest code can be used.
     */
    public function sendOtp(User $user, ?string $phone = null): PhoneOtp
    {
        $this->assertPhone($user, $phone);
        $this->assertWithinSendLimits($user);

        $token = (string) random_int(100000, 999999);

        $otp = PhoneOtp::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'purpose' => self::PURPOSE,
            'expires_at' => now()->addMinutes((int) config('workride.phone_verification.otp_ttl_minutes', 10)),
        ]);

        PhoneOtp::where('user_id', $user->id)
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->whereKeyNot($otp->id)
            ->update(['consumed_at' => now()]);

        $user->notify(new SendPhoneOtp($token));

        return $otp;
    }

    /**
     * Consume the OTP and mark the phone verified. Wrong codes increment the
     * attempts counter; after the cap the code is burned.
     */
    public function verifyOtp(User $user, string $token): void
    {
        $otp = PhoneOtp::where('user_id', $user->id)
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages(['code' => 'No active code found. Request a new one.']);
        }

        $maxAttempts = (int) config('workride.phone_verification.otp_max_attempts', 5);

        if ($otp->attempts >= $maxAttempts) {
            $otp->consume();

            throw ValidationException::withMessages(['code' => 'Too many incorrect attempts. Request a new code.']);
        }

        if ($otp->isExpired()) {
            $otp->consume();

            throw ValidationException::withMessages(['code' => 'This code has expired. Request a new one.']);
        }

        if (! $otp->matches($token)) {
            $otp->recordAttempt();

            throw ValidationException::withMessages(['code' => 'That code is incorrect.']);
        }

        $otp->consume();

        $user->update(['phone_verified_at' => now()]);

        ActivityLog::log($user, 'phone_verified', null, null, []);
    }

    private function assertPhone(User $user, ?string $phone): void
    {
        $value = $phone ? trim($phone) : trim((string) $user->phone);

        if ($value === '' || ! preg_match('/^\+?[0-9][0-9\s\-]{7,18}$/', $value)) {
            throw ValidationException::withMessages(['phone' => 'A valid phone number is required to receive the code.']);
        }

        if ($phone && $phone !== $user->phone) {
            $user->update(['phone' => $phone]);
        }
    }

    private function assertWithinSendLimits(User $user): void
    {
        $cooldown = (int) config('workride.phone_verification.send_cooldown_seconds', 60);
        $dailyLimit = (int) config('workride.phone_verification.send_daily_limit', 5);

        $recent = PhoneOtp::where('user_id', $user->id)
            ->where('purpose', self::PURPOSE)
            ->where('created_at', '>', now()->subSeconds($cooldown))
            ->exists();

        if ($recent) {
            throw ValidationException::withMessages(['phone' => 'Please wait a moment before requesting another code.']);
        }

        $today = PhoneOtp::where('user_id', $user->id)
            ->where('purpose', self::PURPOSE)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($today >= $dailyLimit) {
            throw ValidationException::withMessages(['phone' => 'Too many codes requested today. Try again tomorrow.']);
        }
    }
}
