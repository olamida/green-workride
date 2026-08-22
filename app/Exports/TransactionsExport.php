<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Enumerable
    {
        return Transaction::query()
            ->with('wallet.user')
            ->latest()
            ->limit(1000)
            ->get();
    }

    public function headings(): array
    {
        return ['Reference', 'Date', 'User Email', 'User Name', 'Type', 'Amount', 'Description'];
    }

    public function map($transaction): array
    {
        return [
            $transaction->reference,
            $transaction->created_at->toDateTimeString(),
            $transaction->wallet?->user?->email ?? '',
            $transaction->wallet?->user?->name ?? '',
            $transaction->type->label(),
            number_format((float) $transaction->amount, 2),
            $transaction->description ?? '',
        ];
    }
}
