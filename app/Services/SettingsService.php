<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

/**
 * Runtime overrides for `config/workride.php` persisted in the `settings`
 * table. Values are read override-first with the committed config as fallback,
 * so a missing/empty override behaves exactly like today.
 */
class SettingsService
{
    public const FARE_KEY_PREFIX = 'max_fare_per_corridor.';

    /**
     * Read an override, falling back to the given default when unset.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('key', $key)->first();

        if ($setting === null || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return $setting->value;
    }

    public function has(string $key): bool
    {
        return Setting::where('key', $key)->whereNotNull('value')->where('value', '<>', '')->exists();
    }

    /**
     * Persist an override. Any non-null value is stored as a string so the
     * table stays type-agnostic; numeric lookups cast on read.
     */
    public function set(string $key, mixed $value, ?User $actor = null): Setting
    {
        return Setting::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'updated_by' => $actor?->id],
        );
    }

    /**
     * Remove an override so the committed config default applies again.
     */
    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
    }

    /**
     * Per-corridor fixed fare override (naira), null when the config default
     * should apply. Used by {@see PricingService::fareFor()}.
     */
    public function fareFor(string $corridor): ?float
    {
        $key = self::FARE_KEY_PREFIX.$corridor;

        if (! $this->has($key)) {
            return null;
        }

        return (float) $this->get($key);
    }
}
