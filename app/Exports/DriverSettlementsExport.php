<?php

namespace App\Exports;

use App\Enums\TransactionType;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DriverSettlementsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected $commission;

    protected $union;

    protected $insurance;

    public function __construct()
    {
        $this->commission = (float) config('workride.commission_rate');
        $this->union = (float) config('workride.union_fee_rate');
        $this->insurance = (float) config('workride.insurance_per_trip');
    }

    public function collection(): Enumerable
    {
        return DB::table('transactions as t')
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
            ->get();
    }

    public function headings(): array
    {
        return ['Email', 'Name', 'Rides', 'Fares Gross', 'Commission', 'Union Fee', 'Insurance', 'Earned Net'];
    }

    public function map($row): array
    {
        $gross = (float) $row->fares_gross;
        $net = (float) $row->earned_net;

        return [
            $row->email,
            $row->name,
            $row->rides,
            number_format($gross, 2),
            number_format($gross * $this->commission, 2),
            number_format($gross * $this->union, 2),
            number_format((float) $row->rides * $this->insurance, 2),
            number_format($net, 2),
        ];
    }
}
