<?php

namespace App\Models;

use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;

/**
 * Runtime overrides for `config/workride.php` — kept out of the config file so
 * ops can tune corridor fares without a deploy. `PricingService::fareFor()`
 * consults these via {@see SettingsService} and falls back to
 * the committed config default when no override exists.
 */
class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];
}
