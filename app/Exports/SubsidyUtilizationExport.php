<?php

namespace App\Exports;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SubsidyUtilizationExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Enumerable
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
            $issuedAmt = round((float) $row->issued, 2);
            $spentAmt = round((float) ($spentRow->spent ?? 0), 2);

            return (object) [
                'workplace' => $row->workplace,
                'staff_funded' => (int) $row->staff_funded,
                'issued' => $issuedAmt,
                'spent' => $spentAmt,
                'utilisation' => $issuedAmt > 0 ? round(($spentAmt / $issuedAmt) * 100, 1).'%' : '0%',
            ];
        })->values();
    }

    public function headings(): array
    {
        return ['Workplace', 'Staff Funded', 'Issued', 'Spent', 'Utilisation'];
    }

    public function map($row): array
    {
        return [
            $row->workplace,
            $row->staff_funded,
            number_format($row->issued, 2),
            number_format($row->spent, 2),
            $row->utilisation,
        ];
    }
}
