<?php

namespace App\Jobs;

use App\Services\DriverPromptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Evaluate every corridor for the demand→supply trigger (predicted > 10 and
 * supply < predicted/3) and prompt qualified drivers. Idempotent: the unique
 * per-driver-day-corridor reference means re-runs never double-nudge. The
 * scheduler gates this job on workride.driver_prompts.enabled.
 */
class CalculateDriverPromptsJob implements ShouldQueue
{
    use Queueable;

    public function handle(DriverPromptService $prompts): void
    {
        if (! config('workride.driver_prompts.enabled')) {
            return;
        }

        $prompts->nudgeAll();
    }
}
