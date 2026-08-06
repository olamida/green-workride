<?php

namespace Tests\Feature;

use App\Enums\RideCreditStatus;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Jobs\SendRideCreditRemindersJob;
use App\Models\RideCredit;
use App\Models\User;
use App\Notifications\RideCreditDueSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RideCreditReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('workride.time_bank.enabled', true);
    }

    private function debtor(string $email = 'debtor@workride.ng'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => UserRole::Both,
            'verification_level' => VerificationLevel::NinVerified,
        ]);
    }

    private function credit(User $user, int $dueInDays, ?RideCreditStatus $status = null): RideCredit
    {
        return RideCredit::create([
            'user_id' => $user->id,
            'seats_owed' => 2,
            'seats_repaid' => 0,
            'fare_value' => 1200,
            'due_date' => now()->addDays($dueInDays),
            'status' => $status ?? RideCreditStatus::Owed,
        ]);
    }

    public function test_due_soon_credit_sends_one_database_reminder_and_stamps_idempotency(): void
    {
        Notification::fake();
        $user = $this->debtor();
        $credit = $this->credit($user, 2);

        $sent = (new SendRideCreditRemindersJob(3))->handle();

        $this->assertSame(1, $sent);
        Notification::assertSentTo($user, RideCreditDueSoon::class);
        $this->assertNotNull($credit->fresh()->reminder_sent_at);

        // Idempotent rerun — no second notification, no double stamp.
        $this->assertSame(0, (new SendRideCreditRemindersJob(3))->handle());
        Notification::assertSentToTimes($user, RideCreditDueSoon::class, 1);
    }

    public function test_credit_beyond_reminder_window_is_left_alone(): void
    {
        Notification::fake();
        $user = $this->debtor();
        $credit = $this->credit($user, 10);

        $this->assertSame(0, (new SendRideCreditRemindersJob(3))->handle());

        Notification::assertNothingSent();
        $this->assertNull($credit->fresh()->reminder_sent_at);
    }

    public function test_overdue_and_repaid_credits_never_reminded(): void
    {
        Notification::fake();
        $user = $this->debtor();
        $this->credit($user, -1, RideCreditStatus::Overdue);
        $this->credit($user, 1, RideCreditStatus::Repaid);
        $this->credit($user, 1, RideCreditStatus::Waived);

        $this->assertSame(0, (new SendRideCreditRemindersJob(3))->handle());

        Notification::assertNothingSent();
    }

    public function test_due_today_is_reminded_but_past_due_is_not(): void
    {
        Notification::fake();
        $user = $this->debtor();
        $dueToday = $this->credit($user, 0);

        $this->assertSame(1, (new SendRideCreditRemindersJob(3))->handle());
        Notification::assertSentTo($user, RideCreditDueSoon::class);
        $this->assertNotNull($dueToday->fresh()->reminder_sent_at);
    }
}
