<?php

namespace Database\Seeders;

use App\Enums\VerificationProvider;
use App\Enums\VerificationTier;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationAttempt;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Approved verification trail for the rich demo users (guide §5A identity
 * gatekeeping + §3.6 tiered KYC). Every L2+ user has an approved `nin`
 * record; every L3 user additionally has an approved `driver` record; all
 * users have a `workplace_id` record. Raw NINs are never stored — only the
 * SHA-256 hash + last 4, matching VerificationService::recomputeLevel().
 */
class RichVerificationSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichVerificationSeeder.');

            return;
        }

        $reviewer = User::where('email', config('workride.admin.email'))->first();

        // L1 volunteers: workplace_id approved only.
        $volunteers = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->where('verification_level', 1)
            ->where('role', 'volunteer')
            ->get();

        foreach ($volunteers as $user) {
            Verification::updateOrCreate(['user_id' => $user->id, 'type' => 'workplace_id'], [
                'workplace_id' => $user->workplace_id,
                'document_hash' => hash('sha256', 'demo-staff-id-'.$user->email),
                'status' => 'approved',
                'verified_by' => $reviewer?->id,
                'verified_at' => now()->subDays(20 + $user->id % 30),
            ]);
        }

        // L2 passengers + L3 drivers/both: workplace_id + nin approved.
        $l2 = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->whereIn('verification_level', [2, 3])
            ->get();

        foreach ($l2 as $user) {
            Verification::updateOrCreate(['user_id' => $user->id, 'type' => 'workplace_id'], [
                'workplace_id' => $user->workplace_id,
                'document_hash' => hash('sha256', 'demo-staff-id-'.$user->email),
                'status' => 'approved',
                'verified_by' => $reviewer?->id,
                'verified_at' => now()->subDays(25 + $user->id % 30),
            ]);

            $nin = $this->ninFor($user->email);
            Verification::updateOrCreate(['user_id' => $user->id, 'type' => 'nin'], [
                'nin_last4' => $user->nin_last4 ?? $nin['nin_last4'],
                'document_hash' => $user->nin_hash ?? $nin['nin_hash'],
                'status' => 'approved',
                'provider' => VerificationProvider::IdentityPass,
                'tier' => VerificationTier::Tier2,
                'nimc_reference' => 'NIMC-DEMO-'.$user->id,
                'liveness_score' => 82 + ($user->id % 15),
                'verified_by' => $reviewer?->id,
                'verified_at' => now()->subDays(18 + $user->id % 30),
            ]);
        }

        // L3 users: additionally driver docs approved (the paid-driver gate).
        $l3 = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->where('verification_level', 3)
            ->get();

        foreach ($l3 as $user) {
            Verification::updateOrCreate(['user_id' => $user->id, 'type' => 'driver'], [
                'document_hash' => hash('sha256', 'demo-driver-licence-'.$user->email),
                'status' => 'approved',
                'provider' => VerificationProvider::Smile,
                'tier' => VerificationTier::Tier3,
                'face_match_score' => 88 + ($user->id % 10),
                'liveness_score' => 90 + ($user->id % 8),
                'verified_by' => $reviewer?->id,
                'verified_at' => now()->subDays(15 + $user->id % 30),
            ]);
        }

        // Verification attempts — one per tier per user (audit trail + rate-limit demo).
        foreach (User::query()->where('email', 'like', 'demo%@workride.ng')->get() as $user) {
            $attempts = match ((int) $user->verification_level->value) {
                3 => [
                    [VerificationTier::Tier1, VerificationProvider::Open, 92, 'approved'],
                    [VerificationTier::Tier2, VerificationProvider::IdentityPass, 84, 'approved'],
                    [VerificationTier::Tier3, VerificationProvider::Smile, 93, 'approved'],
                ],
                2 => [
                    [VerificationTier::Tier1, VerificationProvider::Open, 90, 'approved'],
                    [VerificationTier::Tier2, VerificationProvider::IdentityPass, 81, 'approved'],
                ],
                default => [
                    [VerificationTier::Tier1, VerificationProvider::Open, 78, 'approved'],
                ],
            };

            foreach ($attempts as $i => [$tier, $provider, $score, $status]) {
                VerificationAttempt::updateOrCreate(
                    ['user_id' => $user->id, 'tier' => $tier, 'created_at' => now()->subDays(10 + $i * 3 + $user->id % 5)],
                    [
                        'provider' => $provider,
                        'liveness_score' => $score,
                        'status' => $status,
                        'ip_address' => '127.0.0.1',
                    ]
                );
            }
        }

        $this->command?->info('Rich demo verifications seeded (workplace_id/nin/driver approvals + attempts).');
    }
}
