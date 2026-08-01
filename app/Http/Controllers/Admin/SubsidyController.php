<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workplace;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Subsidy management for the Ops Control Tower.
 *
 * MDAs load subsidy_credits into their staff wallets via CSV bulk credit
 * (Workflow 3: Corporate Subsidy). Every credit writes an idempotent
 * transaction row so MDA finance can audit exactly what was disbursed.
 */
class SubsidyController extends Controller
{
    public function index(Request $request)
    {
        $workplaces = Workplace::query()
            ->withCount('users')
            ->get()
            ->map(function (Workplace $workplace) {
                $workplace->subsidy_total = (float) Wallet::whereHas(
                    'user',
                    fn ($query) => $query->where('workplace_id', $workplace->id),
                )->sum('subsidy_credits');

                return $workplace;
            })
            ->sortByDesc('subsidy_total')
            ->values();

        $stats = [
            'subsidy_issued' => (float) Wallet::sum('subsidy_credits'),
            'staff_funded' => Wallet::where('subsidy_credits', '>', 0)->count(),
            'workplaces' => $workplaces->count(),
        ];

        $recent = Transaction::query()
            ->where('type', TransactionType::Subsidy->value)
            ->with(['wallet.user.workplace'])
            ->when($request->integer('workplace_id'), function ($query, int $workplaceId) {
                $query->whereHas('wallet.user', fn ($q) => $q->where('workplace_id', $workplaceId));
            })
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.subsidies', compact('workplaces', 'stats', 'recent'));
    }

    public function bulkCredit(Request $request, WalletService $walletService)
    {
        $data = $request->validate([
            'csv' => ['required', 'file'],
            'workplace_id' => ['nullable', 'exists:workplaces,id'],
        ]);

        $file = $request->file('csv');

        if (! in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return back()->withErrors(['csv' => 'Please upload a .csv or .txt file with email,amount rows.']);
        }

        $rows = $this->readCsv($file->getRealPath());

        $workplaceId = $request->input('workplace_id');
        $batch = 'MDA-'.($workplaceId ?: 'GLOBAL').'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $credited = 0;
        $missing = 0;
        $invalid = 0;
        $totalAmount = 0.0;

        foreach ($rows as $index => $row) {
            $email = $this->extractEmail($row);
            $amount = $this->extractAmount($row);

            if (! $email || ! $amount) {
                $invalid++;

                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $missing++;

                continue;
            }

            $walletService->creditSubsidy(
                $user,
                $amount,
                "{$batch}-{$index}",
                'MDA subsidy credit',
                ['workplace_id' => $workplaceId ? (int) $workplaceId : null],
            );

            $credited++;
            $totalAmount += $amount;
        }

        if ($credited === 0) {
            return back()->withErrors([
                'csv' => 'No valid rows were processed. Expected CSV columns: email,amount (naira).',
            ]);
        }

        $summary = "Subsidy credited: {$credited} staff (₦".number_format($totalAmount, 2).').';

        if ($missing) {
            $summary .= " Skipped {$missing} unknown email(s).";
        }

        if ($invalid) {
            $summary .= " Skipped {$invalid} invalid row(s).";
        }

        return back()->with('status', $summary);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];

        if (! is_readable($path) || ! ($handle = fopen($path, 'r'))) {
            return $rows;
        }

        while (($line = fgetcsv($handle, 4096)) !== false) {
            $line = array_map('trim', $line);

            if (count($line) < 2 || $line[0] === '' || strtolower($line[0]) === 'email') {
                continue;
            }

            $rows[] = $line;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function extractEmail(array $row): ?string
    {
        foreach ($row as $cell) {
            if (filter_var($cell, FILTER_VALIDATE_EMAIL)) {
                return strtolower($cell);
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function extractAmount(array $row): ?float
    {
        foreach (array_reverse($row) as $cell) {
            $amount = (float) preg_replace('/[^\d.]/', '', $cell);

            if ($amount > 0) {
                return round($amount, 2);
            }
        }

        return null;
    }
}
