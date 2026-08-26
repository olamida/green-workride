<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateResumePrompt extends Command
{
    protected $signature = 'workride:resume-prompt';

    protected $description = 'Generate a resume prompt for the next opencode session';

    public function handle(): int
    {
        $repoRoot = base_path();
        $outputPath = $repoRoot.'/resume-prompt.md';

        $prompt = $this->buildPrompt();

        File::put($outputPath, $prompt);

        $this->info("Resume prompt written to {$outputPath}");
        $this->line('');
        $this->line('---');
        $this->line($prompt);

        return self::SUCCESS;
    }

    private function buildPrompt(): string
    {
        $parts = [];

        $parts[] = '# WorkRide — Resume Prompt for Next Session';
        $parts[] = '';
        $parts[] = '> Paste this entire file into the next opencode session to resume seamlessly.';
        $parts[] = '';

        // Current tag
        $parts[] = '## Current State';
        $parts[] = '';
        $parts[] = '- **Tag:** '.$this->getLatestTag();
        $parts[] = '- **Tests:** '.$this->getTestCount().' passing';
        $parts[] = '- **PHP:** '.PHP_VERSION;
        $parts[] = '- **Laravel:** '.app()->version();
        $parts[] = '';

        // What was just completed
        $parts[] = '## Just Completed';
        $parts[] = '';
        $completed = $this->getLatestCompleted();
        if ($completed) {
            $parts[] = $completed;
        } else {
            $parts[] = '- (No recent entry found in DEVELOPMENT-LOG.md)';
        }
        $parts[] = '';

        // Next roadmap items
        $parts[] = '## Next Tasks (from WORKRIDE-ROADMAP.md)';
        $parts[] = '';
        $next = $this->getNextRoadmapItems();
        foreach ($next as $item) {
            $parts[] = "- {$item}";
        }
        $parts[] = '';

        // Feature gates
        $parts[] = '## Active Feature Gates';
        $parts[] = '';
        $gates = $this->getFeatureGates();
        foreach ($gates as $gate => $enabled) {
            $parts[] = "- **{$gate}**: ".($enabled ? 'ON' : 'OFF');
        }
        $parts[] = '';

        // Key config
        $parts[] = '## Key Config';
        $parts[] = '';
        $parts[] = '- `APP_URL`: '.config('app.url');
        $parts[] = '- `WORKRIDE_ANIMATIONS`: '.(config('workride.animations.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_TIME_BANK`: '.(config('workride.time_bank.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_EMPLOYER_PROGRAMS`: '.(config('workride.employer_programs.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_REWARDS`: '.(config('workride.rewards.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_COMMODITIES`: '.(config('workride.commodities.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_MISSIONS`: '.(config('workride.missions.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_LIVENESS`: '.(config('workride.verification.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_FLEET`: '.(config('workride.fleet.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_DEMAND`: '.(config('workride.demand.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_SOFT_HOLD`: '.(config('workride.soft_hold.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_TRIP_TEMPLATES`: '.(config('workride.trip_templates.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_DRIVER_PROMPTS`: '.(config('workride.driver_prompts.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_PUSH`: '.(config('workride.push.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_GUIDE`: '.(config('workride.guide.enabled') ? 'true' : 'false');
        $parts[] = '- `FEATURE_SCHEDULING`: '.(config('workride.scheduling.enabled') ? 'true' : 'false');
        $parts[] = '';

        // Open follow-ups
        $parts[] = '## Open Follow-ups / Blockers';
        $parts[] = '';
        $followups = $this->getOpenFollowups();
        if ($followups) {
            foreach ($followups as $f) {
                $parts[] = "- {$f}";
            }
        } else {
            $parts[] = '- None recorded';
        }
        $parts[] = '';

        // Quick commands
        $parts[] = '## Quick Commands';
        $parts[] = '';
        $parts[] = '```bash';
        $parts[] = '# Run tests';
        $parts[] = 'php artisan test';
        $parts[] = '';
        $parts[] = '# Run DoD ritual (format → static analysis → tests → build)';
        $parts[] = 'vendor\\bin\\pint';
        $parts[] = 'vendor\\bin\\phpstan analyse';
        $parts[] = 'php artisan test';
        $parts[] = 'npm run build';
        $parts[] = '';
        $parts[] = '# Regenerate GTFS feed';
        $parts[] = 'php artisan gtfs:generate';
        $parts[] = '';
        $parts[] = '# Start dev server + queue + logs + vite';
        $parts[] = 'composer run dev';
        $parts[] = '```';
        $parts[] = '';

        // How to use
        $parts[] = '---';
        $parts[] = '';
        $parts[] = '## How to Use';
        $parts[] = '';
        $parts[] = '1. Copy this entire file';
        $parts[] = '2. Start a new opencode session';
        $parts[] = '3. Paste as your first message';
        $parts[] = '4. opencode will know exactly where to continue';

        return implode("\n", $parts);
    }

    private function getLatestTag(): string
    {
        try {
            // Use simpler git command
            $output = shell_exec('git tag --sort=-v:refname 2>nul');
            if ($output === false || $output === null) {
                return 'v0.0.0';
            }
            $tags = array_filter(array_map('trim', explode("\n", $output)));

            return $tags[0] ?? 'v0.0.0';
        } catch (\Throwable) {
            return 'v0.0.0';
        }
    }

    private function getTestCount(): string
    {
        try {
            // Run a fast test (just one test file) to get count without full suite
            $process = proc_open('php artisan test tests/Feature/ExampleTest.php 2>&1', [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ], $pipes);

            if (! is_resource($process)) {
                return 'unknown (run `php artisan test` for count)';
            }

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if (preg_match('/(\d+)\s+tests?\s+\((\d+)\s+assertions?\)/', $output, $m)) {
                return "{$m[1]} tests, {$m[2]} assertions (sample)";
            }

            return 'unknown (run `php artisan test` for count)';
        } catch (\Throwable) {
            return 'unknown (run `php artisan test` for count)';
        }
    }

    private function getLatestCompleted(): string
    {
        $logPath = base_path('DEVELOPMENT-LOG.md');
        if (! File::exists($logPath)) {
            return '- DEVELOPMENT-LOG.md not found';
        }

        $content = File::get($logPath);

        // Find the version history table entries (most reliable)
        if (preg_match_all('/^\|\s*(`v[\d.]+`)\s*\|\s*([^|]+)\s*\|/m', $content, $matches, PREG_SET_ORDER)) {
            // Get the last 3 entries
            $last = array_slice($matches, -3);
            $entries = [];
            foreach ($last as $m) {
                $entries[] = '- '.trim($m[1]).': '.trim($m[2]);
            }

            return implode("\n", $entries);
        }

        // Fallback: look for "Feature modules | ✅" in the status table
        if (preg_match_all('/^\| Feature modules \| ✅ ([^|]+) \|/m', $content, $matches)) {
            $last = array_slice($matches[1], -3);

            return implode("\n", array_map(fn ($m) => '- '.trim($m), $last));
        }

        return '- (parse DEVELOPMENT-LOG.md for latest)';
    }

    /**
     * @return array<string>
     */
    private function getNextRoadmapItems(): array
    {
        $roadmapPath = base_path('WORKRIDE-ROADMAP.md');
        if (! File::exists($roadmapPath)) {
            return ['WORKRIDE-ROADMAP.md not found'];
        }

        $content = File::get($roadmapPath);
        $items = [];

        // Find P1 items that are not done
        if (preg_match_all('/^\|\s*1\.\d+\s*\|\s*([^|]+)\s*\|\s*([^|]+)\s*\|/m', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $task = trim($m[1]);
                $status = trim($m[2]);
                if (stripos($status, 'done') === false && stripos($status, '✅') === false) {
                    $items[] = "[P1] {$task} — {$status}";
                    if (count($items) >= 5) {
                        break;
                    }
                }
            }
        }

        // Also check P2
        if (count($items) < 5 && preg_match_all('/^\|\s*2\.\d+\s*\|\s*([^|]+)\s*\|\s*([^|]+)\s*\|/m', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $task = trim($m[1]);
                $status = trim($m[2]);
                if (stripos($status, 'done') === false && stripos($status, '✅') === false) {
                    $items[] = "[P2] {$task} — {$status}";
                    if (count($items) >= 5) {
                        break;
                    }
                }
            }
        }

        return $items ?: ['No pending roadmap items found'];
    }

    /**
     * @return array<string, bool>
     */
    private function getFeatureGates(): array
    {
        return [
            'FEATURE_TIME_BANK' => config('workride.time_bank.enabled'),
            'FEATURE_EMPLOYER_PROGRAMS' => config('workride.employer_programs.enabled'),
            'FEATURE_REWARDS' => config('workride.rewards.enabled'),
            'FEATURE_COMMODITIES' => config('workride.commodities.enabled'),
            'FEATURE_MISSIONS' => config('workride.missions.enabled'),
            'FEATURE_LIVENESS' => config('workride.verification.enabled'),
            'FEATURE_FLEET' => config('workride.fleet.enabled'),
            'FEATURE_DEMAND' => config('workride.demand.enabled'),
            'FEATURE_SOFT_HOLD' => config('workride.soft_hold.enabled'),
            'FEATURE_TRIP_TEMPLATES' => config('workride.trip_templates.enabled'),
            'FEATURE_DRIVER_PROMPTS' => config('workride.driver_prompts.enabled'),
            'FEATURE_PUSH' => config('workride.push.enabled'),
            'FEATURE_GUIDE' => config('workride.guide.enabled'),
            'FEATURE_SCHEDULING' => config('workride.scheduling.enabled'),
        ];
    }

    /**
     * @return array<string>
     */
    private function getOpenFollowups(): array
    {
        // Could parse DEVELOPMENT-LOG.md for "Open Follow-ups" or similar
        // For now, return known items from roadmap P1
        return [
            'Seeder README for rich demo world (roadmap 1.1)',
            'Google OAuth implementation (roadmap 1.2)',
            'Live seat-counter channel (roadmap 1.3)',
            'FCM push for arrival nudges (roadmap P3.2)',
        ];
    }
}
