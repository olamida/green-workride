<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RideCreditStatus;
use App\Enums\TrustLedgerDirection;
use App\Enums\TrustLedgerType;
use App\Exports\CommunityTrustExport;
use App\Exports\PayItForwardExport;
use App\Http\Controllers\Controller;
use App\Models\CommunityTrust;
use App\Models\RideCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

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
        return Excel::download(
            new CommunityTrustExport,
            'community-trust-ledger-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    /**
     * Pay-it-forward statement (roadmap 3.11): who rode on Time-Bank this
     * month, who repaid, who is overdue, who was waived — the board-governance
     * view of the Trust float. One snapshot per month from the ride_credits
     * rows plus the Trust ledger's float movements for the same window.
     */
    public function payItForward(Request $request)
    {
        $rawMonth = $request->string('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $rawMonth), 422);
        $month = Carbon::parse($rawMonth);

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $credits = RideCredit::with(['user', 'trip'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        $floatIssued = (float) CommunityTrust::where('type', TrustLedgerType::TimeBankFloat)
            ->where('direction', TrustLedgerDirection::Credit)
            ->whereBetween('recorded_at', [$start, $end])
            ->sum('amount');

        $floatReleased = (float) CommunityTrust::where('type', TrustLedgerType::TimeBankFloat)
            ->where('direction', TrustLedgerDirection::Debit)
            ->whereBetween('recorded_at', [$start, $end])
            ->sum('amount');

        $rode = $credits->count();
        $seatsOwed = $credits->sum('seats_owed');
        $seatsRepaid = $credits->sum('seats_repaid');
        $fareValue = round((float) $credits->sum('fare_value'), 2);

        $byStatus = [
            'repaid' => $credits->where('status', RideCreditStatus::Repaid)->count(),
            'owed' => $credits->where('status', RideCreditStatus::Owed)->count(),
            'overdue' => $credits->where('status', RideCreditStatus::Overdue)->count(),
            'waived' => $credits->where('status', RideCreditStatus::Waived)->count(),
        ];

        $perUser = $credits
            ->groupBy('user_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'name' => $first?->user?->name ?? 'Unknown',
                    'email' => $first?->user?->email ?? '—',
                    'credits' => $rows->count(),
                    'seats_owed' => $rows->sum('seats_owed'),
                    'seats_repaid' => $rows->sum('seats_repaid'),
                    'fare_value' => round((float) $rows->sum('fare_value'), 2),
                    'repaid' => $rows->where('status', RideCreditStatus::Repaid)->count(),
                    'overdue' => $rows->where('status', RideCreditStatus::Overdue)->count(),
                    'waived' => $rows->where('status', RideCreditStatus::Waived)->count(),
                ];
            })
            ->values()
            ->sortByDesc('fare_value')
            ->values();

        return view('admin.trust.pay-it-forward', compact(
            'month',
            'credits',
            'floatIssued',
            'floatReleased',
            'rode',
            'seatsOwed',
            'seatsRepaid',
            'fareValue',
            'byStatus',
            'perUser',
        ));
    }

    public function exportPayItForward(Request $request)
    {
        $rawMonth = $request->string('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $rawMonth), 422);
        $month = Carbon::parse($rawMonth);

        return Excel::download(
            new PayItForwardExport($month),
            'pay-it-forward-'.$month->format('Y-m').'.xlsx'
        );
    }
}
