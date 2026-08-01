<?php

namespace App\Services;

use App\Models\ApiCostLog;
use Illuminate\Support\Carbon;

/**
 * Logs every paid external API call and enforces a monthly budget cap.
 *
 * Open-source-first rule: free providers (self-hosted OSRM) still get logged
 * with cost 0 for the audit trail; paid providers (Google Directions, Mapbox)
 * are logged with their real naira cost and refused once the monthly cap hits.
 */
class CostLogger
{
    public function log(string $provider, string $service, float $costNaira, array $meta = []): ApiCostLog
    {
        return ApiCostLog::create([
            'provider' => $provider,
            'service' => $service,
            'cost_naira' => round($costNaira, 2),
            'meta' => $meta,
        ]);
    }

    /**
     * Total naira spent on a provider this calendar month (or all providers).
     */
    public function monthlySpend(?string $provider = null): float
    {
        return (float) ApiCostLog::query()
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('cost_naira');
    }

    /**
     * Is there headroom left in the monthly API budget for a new call?
     */
    public function withinMonthlyCap(float $additionalCost = 0): bool
    {
        $cap = (float) config('workride.api_caps.monthly_naira', 20000);

        return $this->monthlySpend() + $additionalCost <= $cap;
    }

    /**
     * Number of paid calls this month (provider-agnostic).
     */
    public function monthlyCalls(?string $provider = null): int
    {
        return (int) ApiCostLog::query()
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->where('cost_naira', '>', 0)
            ->count();
    }
}
