<?php

namespace App\Jobs;

use App\Enums\RideCreditStatus;
use App\Models\RideCredit;
use App\Notifications\RideCreditDueSoon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pre-due ride-credit reminders (roadmap 3.4): every overnight run finds owed
 * credits whose due_date is inside the reminder window and not yet reminded,
 * sends a gentle nudge, then stamps reminder_sent_at so the nightly rerun
 * never re-reminds. Credits already past due are handed to the overdue flag
 * logic (RideCreditService::flagOverdue) so the debt book ages correctly.
 */
class SendRideCreditRemindersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $remindWithinDays = 3,
    ) {}

    public function handle(): int
    {
        $credits = RideCredit::query()
            ->with('user')
            ->where('status', RideCreditStatus::Owed->value)
            ->whereNull('reminder_sent_at')
            ->where('due_date', '>=', now()->startOfDay())
            ->where('due_date', '<=', now()->startOfDay()->addDays($this->remindWithinDays)->endOfDay())
            ->orderBy('due_date')
            ->get();

        $sent = 0;

        foreach ($credits as $credit) {
            $user = $credit->user;

            if (! $user) {
                continue;
            }

            $user->notify(new RideCreditDueSoon($credit));
            $credit->update(['reminder_sent_at' => now()]);
            $sent++;
        }

        return $sent;
    }
}
