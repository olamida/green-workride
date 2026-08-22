<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Exports\DriverSettlementsExport;
use App\Exports\SubsidyUtilizationExport;
use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        return Excel::download(new TransactionsExport, 'workride-transactions-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Driver settlements — per-driver earned totals plus the fee breakdown.
     */
    public function exportSettlements()
    {
        return Excel::download(new DriverSettlementsExport, 'workride-driver-settlements-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Subsidy utilization per workplace — the MDA palliative audit export.
     */
    public function exportSubsidy()
    {
        return Excel::download(new SubsidyUtilizationExport, 'workride-subsidy-utilization-'.now()->format('Ymd-His').'.xlsx');
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
}
