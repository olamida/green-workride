<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Corridor;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

/**
 * Corridor fare configuration (roadmap 3.6, guide §8 "Settings config via UI"):
 * the ops team tunes the anti-surge per-corridor fares without a deploy.
 * Overrides persist in the `settings` table and `PricingService::fareFor()`
 * reads them override-first, falling back to the committed config default.
 */
class SettingsController extends Controller
{
    public function index(SettingsService $settings)
    {
        $corridors = collect(Corridor::cases())->map(function (Corridor $corridor) use ($settings) {
            $key = SettingsService::FARE_KEY_PREFIX.$corridor->value;

            return [
                'corridor' => $corridor,
                'key' => $key,
                'overridden' => $settings->has($key),
                'fare' => $settings->fareFor($corridor->value)
                    ?? (float) (config("workride.max_fare_per_corridor.{$corridor->value}") ?? 800),
            ];
        });

        return view('admin.settings', ['corridors' => $corridors]);
    }

    public function store(Request $request, SettingsService $settings)
    {
        $data = $request->validate([
            'fares' => ['required', 'array', 'min:1'],
            'fares.kubwa_cbd' => ['nullable', 'numeric', 'min:100', 'max:5000'],
            'fares.nyanya_idu' => ['nullable', 'numeric', 'min:100', 'max:5000'],
            'fares.lugbe_cbd' => ['nullable', 'numeric', 'min:100', 'max:5000'],
        ]);

        $actor = $request->user();
        $updated = 0;

        foreach (Corridor::cases() as $corridor) {
            $key = SettingsService::FARE_KEY_PREFIX.$corridor->value;
            $value = $data['fares'][$corridor->value] ?? null;

            if ($value === null || $value === '') {
                $settings->forget($key);

                continue;
            }

            $previous = $settings->fareFor($corridor->value);
            $settings->set($key, (float) $value, $actor);
            $updated++;

            ActivityLog::log(
                $actor,
                'corridor_fare_updated',
                Setting::class,
                null,
                [
                    'corridor' => $corridor->value,
                    'from' => $previous,
                    'to' => (float) $value,
                ],
            );
        }

        $message = $updated > 0
            ? 'Corridor fares updated ('.count($data['fares']).' field(s) processed, '.$updated.' override(s) written).'
            : 'Fares unchanged — enter a value to override, leave blank to restore the default.';

        return back()->with('status', $message);
    }
}
