<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployerProgramType;
use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\Vehicle;
use App\Models\Workplace;
use App\Services\EmployerLedgerService;
use App\Services\EmployerReportService;
use App\Services\EmployerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Corporate Mobility Program management (guide §2.2 stream #2/#4).
 *
 * Super-admin side: create the employer account + coverage policy, fund the
 * prepaid mobility wallet, and enroll staff by CSV (email[, employee_id]).
 */
class EmployerController extends Controller
{
    public function index()
    {
        $employers = Employer::withCount('members')
            ->with('wallet')
            ->latest()
            ->get();

        return view('admin.employers.index', compact('employers'));
    }

    public function create()
    {
        $workplaces = Workplace::orderBy('name')->get();
        $programTypes = EmployerProgramType::cases();

        return view('admin.employers.create', compact('workplaces', 'programTypes'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $employer = Employer::create($data + [
            'created_by' => $request->user()->id,
            'corridors' => $request->input('corridors'),
        ]);

        return redirect()
            ->route('admin.employers.show', $employer)
            ->with('status', "{$employer->name} registered — fund its mobility wallet to start covering rides.");
    }

    public function show(Employer $employer)
    {
        $employer->load(['members.user', 'wallet.transactions']);
        $wallet = $employer->wallet;

        $stats = [
            'balance' => $wallet ? (float) $wallet->cash_balance : 0.0,
            'members' => $employer->members->count(),
            'active_members' => $employer->members->where('isActive', true)->count(),
            'trips_covered' => $employer->bookings()->count(),
            'coverage_spent' => (float) $employer->bookings()->sum('employer_contribution'),
        ];

        return view('admin.employers.show', compact('employer', 'wallet', 'stats'));
    }

    /**
     * One-click printable CSR / subsidy report (roadmap 3.14, guide §11 #7) —
     * the aggregate CO₂ + coverage statement an MDA takes into its renewal.
     */
    public function report(Request $request, Employer $employer, EmployerReportService $service)
    {
        $month = Carbon::parse($request->string('month', now()->format('Y-m')));

        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month->format('Y-m')), 422);

        $data = $service->report($employer, $month);

        return view('admin.employers.report', $data);
    }

    public function fund(Request $request, Employer $employer, EmployerLedgerService $ledger)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $reference = 'EMP-FUND-'.$employer->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $ledger->fund(
            $employer,
            (float) $data['amount'],
            $reference,
            $data['note'] ?? 'Corporate mobility wallet funding',
        );

        return back()->with('status', 'Mobility wallet funded with ₦'.number_format((float) $data['amount'], 2).'.');
    }

    public function enroll(Request $request, Employer $employer, EmployerService $service)
    {
        $data = $request->validate([
            'csv' => ['required', 'file'],
        ]);

        $file = $request->file('csv');

        if (! in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return back()->withErrors(['csv' => 'Please upload a .csv or .txt file with email[,name][,phone][,employee_id] rows.']);
        }

        $rows = $this->readCsv($file->getRealPath());

        if (empty($rows)) {
            return back()->withErrors(['csv' => 'No valid rows found. Expected CSV columns: email,name,phone,employee_id.']);
        }

        $result = $service->enrollMany($employer, $rows);

        $summary = "Enrolled {$result['enrolled']} staff member(s) for {$employer->name}.";

        if ($result['created']) {
            $summary .= " Created {$result['created']} new account(s) with a temporary password.";
        }

        if ($result['missing']) {
            $summary .= " Skipped {$result['missing']} row(s) without a valid email.";
        }

        if ($result['invalid']) {
            $summary .= " Skipped {$result['invalid']} invalid row(s).";
        }

        return back()->with('status', $summary);
    }

    public function members(Employer $employer)
    {
        $employer->load('members.user');

        return view('admin.employers.members', compact('employer'));
    }

    /**
     * Cross-employer approval queue — everyone who self-requested (Form 1)
     * and is still awaiting their employer's sign-off.
     */
    public function pendingMembers()
    {
        $members = EmployerMember::query()
            ->where('status', 'pending')
            ->with(['user', 'employer'])
            ->latest()
            ->get();

        return view('admin.employers.members-pending', compact('members'));
    }

    public function approveMember(Request $request, EmployerMember $member, EmployerService $service)
    {
        $service->approveMember($member, $request->user());

        return back()->with('status', $member->user->name.' approved — they can now book and use employer coverage.');
    }

    public function rejectMember(Request $request, EmployerMember $member, EmployerService $service)
    {
        $service->rejectMember($member, $request->user());

        return back()->with('status', $member->user->name.' rejected.');
    }

    /**
     * Re-open a membership for review (undo an approval/rejection). The member
     * returns to the pending queue until the employer decides again.
     */
    public function reviewMember(Request $request, EmployerMember $member)
    {
        $member->update(['status' => 'pending']);

        return back()->with('status', $member->user->name.' returned to the approval queue.');
    }

    /**
     * Fleet view — vehicles registered by the employer's staff.
     */
    public function vehicles(Employer $employer)
    {
        $memberIds = $employer->members()->pluck('user_id');

        $vehicles = Vehicle::query()
            ->whereIn('user_id', $memberIds)
            ->with('owner')
            ->latest()
            ->get();

        return view('admin.employers.vehicles', compact('employer', 'vehicles'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'rc_number' => ['nullable', 'string', 'max:40', Rule::unique('employers', 'rc_number')],
            'address' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:40'],
            'workplace_id' => ['nullable', 'exists:workplaces,id'],
            'program_type' => ['required', Rule::enum(EmployerProgramType::class)],
            'percent_covered' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_per_trip' => ['nullable', 'numeric', 'min:0'],
            'max_monthly_per_employee' => ['nullable', 'numeric', 'min:0'],
            'corridors' => ['nullable', 'array'],
            'corridors.*' => ['string', Rule::in(['kubwa_cbd', 'nyanya_idu', 'lugbe_cbd'])],
            'covered_direction' => ['nullable', Rule::in(['to_work', 'from_work'])],
            'active' => ['nullable', 'boolean'],
        ]);
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

            // Header rows are passed through — EmployerService::parseRow
            // detects them and maps columns by name (email,name,phone,employee_id).
            if (count($line) < 1 || $line[0] === '') {
                continue;
            }

            $rows[] = $line;
        }

        fclose($handle);

        return $rows;
    }
}
