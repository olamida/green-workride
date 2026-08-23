<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\User;
use App\Services\EmployerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employer self-service enrollment portal (roadmap P3.5).
 * Allows employer admins to manage their own staff roster via CSV upload.
 */
class EmployerEnrollmentController extends Controller
{
    public function __construct(
        private EmployerService $employerService
    ) {}

    /**
     * Show the employer's enrollment dashboard.
     */
    public function index(Employer $employer): View
    {
        $employer->load(['members.user']);

        $stats = [
            'total' => $employer->members->count(),
            'active' => $employer->members->where('isActive', true)->count(),
            'pending' => $employer->members->where('isPending', true)->count(),
            'admins' => $employer->members->where('is_employer_admin', true)->count(),
        ];

        return view('employer.enrollment.index', compact('employer', 'stats'));
    }

    /**
     * Handle CSV upload for staff enrollment.
     */
    public function store(Request $request, Employer $employer): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file'],
        ]);

        $file = $request->file('csv');

        if (! in_array(strtolower($file->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return back()->withErrors([
                'csv' => 'Please upload a .csv or .txt file with email[,name][,phone][,employee_id] rows.',
            ]);
        }

        $rows = $this->readCsv($file->getRealPath());

        if (empty($rows)) {
            return back()->withErrors([
                'csv' => 'No valid rows found. Expected CSV columns: email,name,phone,employee_id.',
            ]);
        }

        $result = $this->employerService->enrollMany($employer, $rows);

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

    /**
     * Toggle employer admin status for a member.
     */
    public function toggleAdmin(Request $request, Employer $employer, EmployerMember $member): RedirectResponse
    {
        // Cannot demote the last admin
        if ($member->is_employer_admin) {
            $adminCount = $employer->members()->where('is_employer_admin', true)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['member' => 'Cannot demote the last employer admin.']);
            }
        }

        $member->update(['is_employer_admin' => ! $member->is_employer_admin]);

        $action = $member->is_employer_admin ? 'promoted to employer admin' : 'demoted from employer admin';

        /** @var User|null $memberUser */
        $memberUser = $member->user;
        $memberName = $memberUser->name ?? 'Member';

        return back()->with('status', "{$memberName} {$action}.");
    }

    /**
     * Remove a member from the employer's roster.
     */
    public function destroy(Request $request, Employer $employer, EmployerMember $member): RedirectResponse
    {
        // Prevent removing the last admin
        if ($member->is_employer_admin) {
            $adminCount = $employer->members()->where('is_employer_admin', true)->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['member' => 'Cannot remove the last employer admin.']);
            }
        }

        /** @var User|null $memberUser */
        $memberUser = $member->user;
        $name = $memberUser->name ?? 'Member';
        $member->delete();

        return back()->with('status', "{$name} removed from {$employer->name} roster.");
    }

    /**
     * Read and parse CSV file.
     *
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];

        if (! is_readable($path) || ! ($handle = fopen($path, 'r'))) {
            return $rows;
        }

        while (($line = fgetcsv($handle, 4096)) !== false) {
            $line = array_map(static fn ($v): string => trim((string) $v), $line);

            if (empty($line)) {
                continue;
            }
            $firstCell = $line[0] ?? '';
            if ($firstCell === '') {
                continue;
            }

            $rows[] = $line;
        }

        fclose($handle);

        return $rows;
    }
}
