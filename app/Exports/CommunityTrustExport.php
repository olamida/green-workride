<?php

namespace App\Exports;

use App\Models\CommunityTrust;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CommunityTrustExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Enumerable
    {
        return CommunityTrust::query()
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return ['Reference', 'Type', 'Direction', 'Amount', 'Balance After', 'Recorded At', 'Meta'];
    }

    public function map($entry): array
    {
        return [
            $entry->reference,
            $entry->type->value,
            $entry->direction->value,
            number_format((float) $entry->amount, 2, '.', ''),
            number_format((float) $entry->balance_after, 2, '.', ''),
            $entry->recorded_at->toDateTimeString(),
            json_encode($entry->meta ?? []),
        ];
    }
}
