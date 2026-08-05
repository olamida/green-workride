<?php

namespace Database\Seeders\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Shared plumbing for the rich WorkRide demo data suite.
 *
 * Every rich seeder writes demo-only records and must be safe to re-run
 * (`php artisan db:seed` more than once) and safe to gate on the feature
 * flags. Demo users carry emails like `demo001@workride.ng` — the marker a
 * dependent seeder checks before inserting a second copy of its data.
 */
trait InteractsWithDemoData
{
    /**
     * Marker action recorded in activity_logs once the ENTIRE rich suite has
     * completed — NOT the first demo user (demo001 exists after RichUserSeeder
     * alone, so using a user as the guard would make every following seeder
     * skip itself on a fresh run). The last seeder in the chain writes it.
     */
    private string $demoMarkerAction = 'rich_suite_seeded';

    /**
     * Has the full rich demo suite already finished seeding?
     */
    protected function demoSynced(): bool
    {
        return ActivityLog::where('action', $this->demoMarkerAction)->exists();
    }

    /**
     * Mark the suite as complete. Called by the LAST seeder in the chain so a
     * re-run of `db:seed` skips every dependent seeder instead of duplicating.
     */
    protected function markSuiteSeeded(): void
    {
        $user = User::where('email', 'demo001@workride.ng')->first()
            ?? User::where('email', 'demo@workride.ng')->first();

        ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $this->demoMarkerAction,
            'model_type' => 'demo-suite',
            'model_id' => null,
            'changes' => ['seeded_at' => now()->toIso8601String()],
        ]);
    }

    /**
     * Shared demo password (mirrors DemoUserSeeder).
     */
    protected function demoPassword(): string
    {
        return config('workride.demo.password', 'demo1234');
    }

    /**
     * A single pre-hashed copy of the demo password. The `hashed` cast is
     * smart enough to skip re-hashing, so sharing one hash keeps the seeder
     * fast (one bcrypt cost per suite, not per user).
     */
    protected function demoPasswordHash(): string
    {
        return Hash::make($this->demoPassword());
    }

    /**
     * Deterministic demo phone number (unique per index, +23470 series).
     */
    protected function demoPhone(int $i): string
    {
        return '+23470'.str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT);
    }

    /**
     * Deterministic NIN hash + last 4 for a demo email (raw NIN never stored).
     */
    protected function ninFor(string $email): array
    {
        $digits = substr(hash('sha256', 'nin-demo-'.$email), 0, 12);
        $last4 = substr(preg_replace('/\D/', '', $digits), -4);
        if (strlen($last4) !== 4) {
            $last4 = '0000';
        }

        return [
            'nin_hash' => hash('sha256', 'nin-demo-'.$email),
            'nin_last4' => $last4,
        ];
    }

    /**
     * Deterministic unique reference for a seeded record.
     */
    protected function demoReference(string $prefix, int $i): string
    {
        return sprintf('%s-DEMO-%05d', strtoupper($prefix), $i);
    }
}
