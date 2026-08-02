<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RemittanceStatus;
use App\Http\Controllers\Controller;
use App\Models\StakeholderRemittance;
use App\Models\Union;
use App\Services\StakeholderService;

/**
 * Stakeholder Control Tower (guide §10): union chapters, the per-trip
 * remittance ledger and the daily settlement run. We never fight the unions —
 * we make them agents.
 */
class StakeholderController extends Controller
{
    public function index()
    {
        $unions = Union::withCount('remittances as remittance_count')
            ->withSum('remittances as pending_total', 'amount')
            ->orderBy('name')
            ->get();

        $remittances = StakeholderRemittance::with(['union', 'trip'])
            ->latest()
            ->limit(25)
            ->get();

        $totals = [
            'pending_amount' => StakeholderRemittance::where('status', RemittanceStatus::Pending)->sum('amount'),
            'paid_amount' => StakeholderRemittance::where('status', RemittanceStatus::Paid)->sum('amount'),
        ];

        return view('admin.stakeholders.index', compact('unions', 'remittances', 'totals'));
    }

    public function settle(StakeholderService $stakeholders)
    {
        $settled = $stakeholders->settleDue();

        return back()->with('status', "Settled {$settled} pending remittance(s) to union accounts.");
    }
}
