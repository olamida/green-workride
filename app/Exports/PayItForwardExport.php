<?php

namespace App\Exports;

use App\Models\RideCredit;
use Carbon\Carbon;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayItForwardExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected Carbon $month;

    public function __construct(Carbon $month)
    {
        $this->month = $month;
    }

    public function collection(): Enumerable
    {
        $start = $this->month->copy()->startOfMonth();
        $end = $this->month->copy()->endOfMonth();

        return RideCredit::with('user')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Created At', 'Name', 'Email', 'Seats Owed', 'Seats Repaid', 'Fare Value', 'Status', 'Due Date'];
    }

    public function map($credit): array
    {
        return [
            $credit->created_at->toDateTimeString(),
            $credit->user->name ?? 'Unknown',
            $credit->user->email ?? '—',
            $credit->seats_owed,
            $credit->seats_repaid,
            number_format((float) $credit->fare_value, 2, '.', ''),
            $credit->status->value,
            $credit->due_date?->toDateString() ?? '—',
        ];
    }
}
