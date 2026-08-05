<?php

namespace App\Services;

use App\Enums\TrustLedgerDirection;
use App\Enums\TrustLedgerType;
use App\Models\CommunityTrust;

/**
 * Community Trust ledger (guide §2.1 + Design Review 3). The Trust owns the
 * GTFS + road data and the 15% profit share; the Time-Bank float it funds is
 * recorded here so "ride now, pay later" is auditable rather than a black box.
 *
 * Every entry is idempotent on its reference — re-running a credit or debit
 * for the same reference is a no-op that returns the existing row.
 */
class TrustService
{
    public function enabled(): bool
    {
        return (bool) config('workride.time_bank.enabled', false);
    }

    public function balance(?TrustLedgerType $type = null): float
    {
        $credits = CommunityTrust::query()
            ->where('direction', TrustLedgerDirection::Credit)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->sum('amount');

        $debits = CommunityTrust::query()
            ->where('direction', TrustLedgerDirection::Debit)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->sum('amount');

        return round((float) $credits - (float) $debits, 2);
    }

    /**
     * The Trust extends value: a Time-Bank float, a subsidy injection, etc.
     * Idempotent on $reference.
     */
    public function credit(TrustLedgerType $type, float $amount, string $reference, array $meta = []): CommunityTrust
    {
        return $this->record(TrustLedgerDirection::Credit, $type, $amount, $reference, $meta);
    }

    /**
     * The Trust receives value / releases a liability (e.g. a repaid seat).
     * Idempotent on $reference.
     */
    public function debit(TrustLedgerType $type, float $amount, string $reference, array $meta = []): CommunityTrust
    {
        return $this->record(TrustLedgerDirection::Debit, $type, $amount, $reference, $meta);
    }

    private function record(
        TrustLedgerDirection $direction,
        TrustLedgerType $type,
        float $amount,
        string $reference,
        array $meta
    ): CommunityTrust {
        $amount = round(max($amount, 0), 2);

        if ($amount <= 0) {
            $existing = CommunityTrust::where('reference', $reference)->first();

            return $existing ?? CommunityTrust::create([
                'direction' => $direction,
                'type' => $type,
                'amount' => 0,
                'balance_after' => $this->balance($type),
                'reference' => $reference,
                'meta' => $meta,
                'recorded_at' => now(),
            ]);
        }

        $existing = CommunityTrust::where('reference', $reference)->first();

        if ($existing) {
            return $existing;
        }

        $balanceAfter = $this->balance($type)
            + ($direction === TrustLedgerDirection::Credit ? $amount : -$amount);

        return CommunityTrust::create([
            'direction' => $direction,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reference' => $reference,
            'meta' => $meta,
            'recorded_at' => now(),
        ]);
    }
}
