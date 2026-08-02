<?php

namespace App\Services;

use App\Enums\RemittanceStatus;
use App\Models\Booking;
use App\Models\StakeholderRemittance;
use App\Models\Trip;
use App\Models\Union;

/**
 * Stakeholder management (guide §10): every paid ride auto-remits the union's
 * share (fare × union commission) to its corridor's chapter, reference-keyed
 * and idempotent. The daily Moniepoint settlement flips pending → paid.
 */
class StakeholderService
{
    public function __construct(private PricingService $pricing) {}

    /**
     * Record a pending remittance per paid, carried booking. Idempotent via the
     * REM-{bookingId} reference. Free/volunteer rides remit nothing.
     */
    public function recordForTrip(Trip $trip): int
    {
        if ($trip->is_free_volunteer) {
            return 0;
        }

        $union = $this->unionFor($trip->corridor->value);
        $created = 0;

        if (! $union) {
            return 0;
        }

        foreach ($trip->bookings as $booking) {
            if (! $booking->fare_paid || ! in_array($booking->status->value, ['boarded', 'completed'], true)) {
                continue;
            }

            $amount = $this->pricing->unionFee((float) $booking->fare_paid);

            if ($amount <= 0) {
                continue;
            }

            StakeholderRemittance::firstOrCreate(
                ['reference' => "REM-{$booking->id}"],
                [
                    'trip_id' => $trip->id,
                    'union_id' => $union->id,
                    'amount' => $amount,
                    'status' => RemittanceStatus::Pending,
                    'meta' => ['fare' => $booking->fare_paid, 'corridor' => $trip->corridor->value],
                ]
            )->wasRecentlyCreated && $created++;
        }

        return $created;
    }

    public function settleDue(): int
    {
        return StakeholderRemittance::query()
            ->where('status', RemittanceStatus::Pending)
            ->get()
            ->each(fn (StakeholderRemittance $r) => $r->markPaid())
            ->count();
    }

    public function unionFor(string $corridor): ?Union
    {
        return Union::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('corridor', $corridor)->orWhereNull('corridor'))
            ->orderByRaw('CASE WHEN corridor = ? THEN 0 ELSE 1 END', [$corridor])
            ->first();
    }
}
