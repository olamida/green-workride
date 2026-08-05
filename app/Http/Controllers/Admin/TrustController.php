<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrustLedgerDirection;
use App\Enums\TrustLedgerType;
use App\Http\Controllers\Controller;
use App\Models\CommunityTrust;

/**
 * Community Trust reconciliation (guide §2.1 + §19 traceability): the auditable
 * float behind Time-Bank "ride now, drive later" and the 15% profit share.
 * The report recomputes every running balance from the entries themselves, so
 * a drift in `balance_after` (manual edit, double-write, missed entry) surfaces
 * as a flagged mismatch instead of a silent black box.
 */
class TrustController extends Controller
{
    public function index()
    {
        $entries = CommunityTrust::query()
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $byType = [];
        $floatIssued = 0.0;
        $floatReleased = 0.0;

        foreach ($entries as $entry) {
            $type = $entry->type;
            $byType[$type->value] ??= ['type' => $type, 'credits' => 0.0, 'debits' => 0.0, 'balance' => 0.0];

            if ($entry->direction === TrustLedgerDirection::Credit) {
                $byType[$type->value]['credits'] += (float) $entry->amount;
                $byType[$type->value]['balance'] += (float) $entry->amount;
            } else {
                $byType[$type->value]['debits'] += (float) $entry->amount;
                $byType[$type->value]['balance'] -= (float) $entry->amount;
            }

            if ($type === TrustLedgerType::TimeBankFloat) {
                $entry->direction === TrustLedgerDirection::Credit
                    ? $floatIssued += (float) $entry->amount
                    : $floatReleased += (float) $entry->amount;
            }
        }

        $total = array_sum(array_map(fn ($row) => $row['balance'], $byType));

        // Reconciliation pass: rebuild each running balance from zero and compare
        // to what was stored at write time.
        $running = [];
        $mismatchReferences = [];

        foreach ($entries as $entry) {
            $running[$entry->type->value] ??= 0.0;
            $running[$entry->type->value] += $entry->direction === TrustLedgerDirection::Credit
                ? (float) $entry->amount
                : -(float) $entry->amount;

            $expected = round($running[$entry->type->value], 2);

            if (abs((float) $entry->balance_after - $expected) > 0.005) {
                $mismatchReferences[] = $entry->reference;
            }
        }

        return view('admin.trust.index', compact(
            'entries',
            'byType',
            'total',
            'floatIssued',
            'floatReleased',
            'mismatchReferences',
        ));
    }

    public function export()
    {
        $entries = CommunityTrust::query()
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $csv = fopen('php://temp', 'w');
        fputcsv($csv, ['reference', 'type', 'direction', 'amount', 'balance_after', 'recorded_at', 'meta']);

        foreach ($entries as $entry) {
            fputcsv($csv, [
                $entry->reference,
                $entry->type->value,
                $entry->direction->value,
                number_format((float) $entry->amount, 2, '.', ''),
                number_format((float) $entry->balance_after, 2, '.', ''),
                $entry->recorded_at->toDateTimeString(),
                json_encode($entry->meta ?? []),
            ]);
        }

        rewind($csv);

        return response(stream_get_contents($csv), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="community-trust-ledger.csv"',
        ]);
    }
}
