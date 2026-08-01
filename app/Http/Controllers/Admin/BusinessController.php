<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 7 — Business dashboard for the Ops Control Tower. Turns the wallet,
 * booking and subsidy ledgers into the funding metrics a CIC board / investor
 * needs: gross revenue, MRR, driver earnings, commission, subsidy utilization,
 * and per-corridor / per-day charts — plus CSV exports of each ledger.
 */
class BusinessController extends Controller
{
    public function index()
    {
        return view('admin.business', [
            'stats' => $this->stats(),
            'revenueByDay' => $this->revenueByDay(),
            'tripsByCorridor' => $this->tripsByCorridor(),
            'subsidyByWorkplace' => $this->subsidyByWorkplace(),
        ]);
    }

    /**
     * All wallet transactions — CSV export for the accounts ledger.
     */
    public function exportTransactions()
    {
        $rows = Transaction::query()
            ->with('wallet.user')
            ->latest()
            ->limit(1000)
            ->get()
            ->map(fn (Transaction $t) => [
                'reference' => $t->reference,
                'date' => $t->created_at->toDateTimeString(),
                'user' => $t->wallet?->user?->email ?? '',
                'user_name' => $t->wallet?->user?->name ?? '',
                'type' => $t->type->label(),
                'amount' => number_format((float) $t->amount, 2),
                'description' => $t->description ?? '',
            ]);

        return $this->csv('workride-transactions-'.now()->format('Ymd-His'), ['reference', 'date', 'user', 'user_name', 'type', 'amount', 'description'], $rows);
    }

    /**
     * Driver settlements — per-driver earned totals plus the fee breakdown.
     */
    public function exportSettlements()
    {
        $commission = (float) config('workride.commission_rate');
        $union = (float) config('workride.union_fee_rate');
        $insurance = (float) config('workride.insurance_per_trip');

        $rows = DB::table('transactions as t')
            ->join('wallets as w', 'w.id', '=', 't.wallet_id')
            ->join('users as u', 'u.id', '=', 'w.user_id')
            ->where('t.type', TransactionType::Earned->value)
            ->groupBy('u.id', 'u.name', 'u.email')
            ->select(
                'u.email',
                'u.name',
                DB::raw('COUNT(t.id) as rides'),
                DB::raw('SUM(t.amount) as earned_net'),
                DB::raw('SUM(t.meta->>"$.fare") as fares_gross'),
            )
            ->get()
            ->map(function ($row) use ($commission, $union, $insurance) {
                $gross = (float) $row->fares_gross;
                $net = (float) $row->earned_net;

                return [
                    'email' => $row->email,
                    'name' => $row->name,
                    'rides' => $row->rides,
                    'fares_gross' => number_format($gross, 2),
                    'commission' => number_format($gross * $commission, 2),
                    'union_fee' => number_format($gross * $union, 2),
                    'insurance' => number_format((float) $row->rides * $insurance, 2),
                    'earned_net' => number_format($net, 2),
                ];
            });

        return $this->csv('workride-driver-settlements-'.now()->format('Ymd-His'), ['email', 'name', 'rides', 'fares_gross', 'commission', 'union_fee', 'insurance', 'earned_net'], $rows);
    }

    /**
     * Subsidy utilization per workplace — the MDA palliative audit export.
     */
    public function exportSubsidy()
    {
        $rows = $this->subsidyByWorkplace()
            ->map(fn (array $row) => [
                'workplace' => $row['workplace'],
                'staff_funded' => $row['staff_funded'],
                'issued' => number_format($row['issued'], 2),
                'spent' => number_format($row['spent'], 2),
                'utilisation' => $row['issued'] > 0 ? number_format(($row['spent'] / $row['issued']) * 100, 1).'%' : '0%',
            ]);

        return $this->csv('workride-subsidy-utilization-'.now()->format('Ymd-His'), ['workplace', 'staff_funded', 'issued', 'spent', 'utilisation'], $rows);
    }

    /**
     * KPI block: revenue, MRR, earnings, commission, subsidy, payouts.
     */
    private function stats(): array
    {
        $capturedStatuses = [BookingStatus::Boarded->value, BookingStatus::Completed->value];

        $grossRevenue = (float) Booking::whereIn('status', $capturedStatuses)
            ->where('fare_paid', '>', 0)
            ->sum('fare_paid');

        $thisMonthStart = now()->startOfMonth();

        $mrr = (float) Booking::whereIn('status', $capturedStatuses)
            ->where('fare_paid', '>', 0)
            ->where('updated_at', '>=', $thisMonthStart)
            ->sum('fare_paid');

        $paidRidesCount = Booking::whereIn('status', $capturedStatuses)
            ->whereNotIn('payment_method', [PaymentMethod::Free->value, PaymentMethod::RideCredit->value])
            ->count();

        $commissionRate = (float) config('workride.commission_rate');
        $unionRate = (float) config('workride.union_fee_rate');
        $insurance = (float) config('workride.insurance_per_trip');

        $driverEarnings = (float) Transaction::where('type', TransactionType::Earned->value)->sum('amount');
        $p2pFees = (float) Transaction::where('type', TransactionType::Fee->value)->sum('amount');
        $payouts = (float) Payout::sum('amount');

        $subsidyIssued = (float) Transaction::where('type', TransactionType::Subsidy->value)->sum('amount');
        $subsidySpent = (float) Booking::whereIn('status', $capturedStatuses)
            ->where('payment_method', PaymentMethod::SubsidyCredit->value)
            ->sum('fare_paid');

        $wallet = (object) Wallet::query()->selectRaw(
            'COALESCE(SUM(cash_balance), 0) as cash, COALESCE(SUM(subsidy_credits), 0) as subsidy, COALESCE(SUM(earned_balance), 0) as earned, COALESCE(SUM(cash_collected_log), 0) as cash_collected'
        )->first();

        return [
            'gross_revenue' => $grossRevenue,
            'mrr' => $mrr,
            'driver_earnings' => $driverEarnings,
            'commission' => $grossRevenue * $commissionRate,
            'union_fees' => $grossRevenue * $unionRate,
            'insurance' => $paidRidesCount * $insurance,
            'p2p_fees' => $p2pFees,
            'payouts' => $payouts,
            'subsidy_issued' => $subsidyIssued,
            'subsidy_spent' => $subsidySpent,
            'subsidy_remaining' => (float) $wallet->subsidy,
            'cash_held' => (float) $wallet->cash,
            'earned_held' => (float) $wallet->earned,
            'cash_collected_log' => (float) $wallet->cash_collected,
            'paid_rides' => $paidRidesCount,
        ];
    }

    /**
     * Last 14 days of captured fare revenue — the "revenue per day" chart.
     */
    private function revenueByDay(): array
    {
        $rows = Booking::whereIn('status', [BookingStatus::Boarded->value, BookingStatus::Completed->value])
            ->where('fare_paid', '>', 0)
            ->where('updated_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(updated_at) as day, SUM(fare_paid) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => round((float) $v, 2))
            ->toArray();

        $series = [];
        foreach (CarbonPeriod::create(now()->subDays(13), now()) as $date) {
            $key = $date->format('Y-m-d');
            $series[] = [
                'day' => $date->format('d M'),
                'total' => $rows[$key] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * Trips per corridor (all statuses) — the corridor volume chart.
     */
    private function tripsByCorridor(): array
    {
        return DB::table('trips')
            ->select('corridor', DB::raw('COUNT(*) as total'))
            ->groupBy('corridor')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'corridor' => strtoupper(str_replace('_', '-', $row->corridor)),
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Per-workplace subsidy issued + spent — the MDA palliative audit table.
     */
    private function subsidyByWorkplace(): Collection
    {
        $issued = DB::table('transactions as t')
            ->join('wallets as w', 'w.id', '=', 't.wallet_id')
            ->join('users as u', 'u.id', '=', 'w.user_id')
            ->leftJoin('workplaces as wp', 'wp.id', '=', 'u.workplace_id')
            ->where('t.type', TransactionType::Subsidy->value)
            ->groupBy('wp.id', 'wp.name')
            ->select(
                'wp.id',
                DB::raw('COALESCE(wp.name, "No workplace") as workplace'),
                DB::raw('COUNT(DISTINCT u.id) as staff_funded'),
                DB::raw('SUM(t.amount) as issued'),
            )
            ->get()
            ->keyBy('id');

        $spent = DB::table('bookings as b')
            ->join('users as u', 'u.id', '=', 'b.passenger_id')
            ->leftJoin('workplaces as wp', 'wp.id', '=', 'u.workplace_id')
            ->where('b.payment_method', PaymentMethod::SubsidyCredit->value)
            ->whereIn('b.status', [BookingStatus::Boarded->value, BookingStatus::Completed->value])
            ->groupBy('wp.id')
            ->select('wp.id', DB::raw('SUM(b.fare_paid) as spent'))
            ->get()
            ->keyBy('id');

        return $issued->map(function ($row) use ($spent) {
            $spentRow = $spent->get($row->id);

            return [
                'workplace' => $row->workplace,
                'staff_funded' => (int) $row->staff_funded,
                'issued' => round((float) $row->issued, 2),
                'spent' => round((float) ($spentRow->spent ?? 0), 2),
            ];
        })->values();
    }

    private function csv(string $filename, array $headers, iterable $rows)
    {
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);

        foreach ($rows as $row) {
            fputcsv($csv, array_values((array) $row));
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ]);
    }
}
