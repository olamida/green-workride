<?php

namespace App\Services;

use App\Enums\EmployerCoverageType;
use App\Enums\EmployerJoinVia;
use App\Enums\EmployerMemberStatus;
use App\Enums\EmployerProgramType;
use App\Enums\VerificationLevel;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\EmployerWelcome;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Corporate Mobility Program engine — computes how much an employer pays for
 * a staff commute, per the program's policy (guide §2.2 streams #2/#4).
 *
 * Coverage precedence:
 *   1. feature flag + active employer + active membership + corridor scope
 *   2. program policy (full / one_way / percent / capped)
 *   3. hard per-trip cap + per-employee monthly cap
 *   4. prepaid wallet affordability (never go negative)
 *
 * Two enrollment forms (guide §7):
 *   Form 1 — the employee self-registers and requests to join (pending);
 *            an admin approval activates the membership AND grants Level 1
 *            workplace verification, because employer confirmation IS the
 *            workplace check (guide's "workplace=1").
 *   Form 2 — the organisation uploads a roster; staff without an account are
 *            auto-created (phone-verified + Level 1) so they can book
 *            immediately and use employer coverage.
 */
class EmployerService
{
    /**
     * Origin zone of each corridor (the "from" leg).
     */
    public const ORIGIN_ZONES = [
        'kubwa_cbd' => 'KUBWA',
        'nyanya_idu' => 'NYANYA',
        'lugbe_cbd' => 'LUGBE',
    ];

    /**
     * Destination zone of each corridor (the "to" leg).
     */
    public const DESTINATION_ZONES = [
        'kubwa_cbd' => 'CBD',
        'nyanya_idu' => 'IDU',
        'lugbe_cbd' => 'CBD',
    ];

    public function __construct(private EmployerLedgerService $ledger) {}

    /**
     * Coverage decision for one booking attempt.
     *
     * @return array{0: float, 1: ?EmployerCoverageType} [contribution, coverage type]
     */
    public function coverageFor(Employer $employer, Trip $trip, User $passenger, float $fare): array
    {
        if (! $this->enabled()) {
            return [0.0, null];
        }

        if (! $employer->isActive()) {
            return [0.0, null];
        }

        if (! $this->activeMember($employer, $passenger)) {
            return [0.0, null];
        }

        if (! $employer->coversCorridor($trip->corridor?->value)) {
            return [0.0, null];
        }

        if ($fare <= 0) {
            return [0.0, null];
        }

        [$amount, $type] = $this->policyAmount($employer, $trip, $fare);

        $amount = round($amount, 2);

        if ($amount <= 0) {
            return [0.0, null];
        }

        // Hard per-trip cap applies regardless of program type.
        if ($employer->max_per_trip !== null) {
            $amount = min($amount, round((float) $employer->max_per_trip, 2));
        }

        // Per-employee monthly budget headroom.
        if ($employer->max_monthly_per_employee !== null) {
            $remaining = round((float) $employer->max_monthly_per_employee - $this->monthlySpent($employer, $passenger), 2);
            $amount = min($amount, max($remaining, 0.0));
        }

        $amount = min($amount, $fare);

        if ($amount <= 0) {
            return [0.0, null];
        }

        try {
            $this->ledger->assertAffordable($employer, $amount);
        } catch (ValidationException) {
            return [0.0, null];
        }

        return [$amount, $type];
    }

    /**
     * Best coverage across the passenger's active employer memberships.
     *
     * @return array{0: float, 1: ?EmployerCoverageType, 2: ?Employer}
     */
    public function bestCoverage(Trip $trip, User $passenger, float $fare): array
    {
        if (! $this->enabled() || $fare <= 0) {
            return [0.0, null, null];
        }

        $memberships = $passenger->employerMemberships()
            ->where('status', EmployerMemberStatus::Active->value)
            ->with('employer')
            ->get();

        foreach ($memberships as $membership) {
            if (! $membership->employer) {
                continue;
            }

            [$amount, $type] = $this->coverageFor($membership->employer, $trip, $passenger, $fare);

            if ($amount > 0) {
                return [$amount, $type, $membership->employer];
            }
        }

        return [0.0, null, null];
    }

    public function activeMember(Employer $employer, User $user): ?EmployerMember
    {
        return $employer->members()
            ->where('user_id', $user->id)
            ->where('status', EmployerMemberStatus::Active->value)
            ->first();
    }

    /**
     * Employer-paid amount for this member in the given month.
     */
    public function monthlySpent(Employer $employer, User $member, ?Carbon $month = null): float
    {
        $start = ($month ?? now())->copy()->startOfMonth();
        $end = ($month ?? now())->copy()->endOfMonth();

        return round((float) Booking::query()
            ->where('employer_id', $employer->id)
            ->where('passenger_id', $member->id)
            ->whereIn('status', ['confirmed', 'boarded', 'completed', 'no_show'])
            ->whereBetween('created_at', [$start, $end])
            ->sum('employer_contribution'), 2);
    }

    /**
     * Form 1 — an employee requests to join an employer program. The request
     * sits at `pending` until the Control Tower approves it. Idempotent for
     * active/pending members; rejected members may request again.
     */
    public function requestJoin(Employer $employer, User $user): EmployerMember
    {
        $this->assertEnabled();

        if (! $employer->isActive()) {
            throw ValidationException::withMessages(['employer' => 'This employer program is not active right now.']);
        }

        $existing = $employer->members()->where('user_id', $user->id)->first();

        if ($existing && $existing->status === EmployerMemberStatus::Suspended) {
            throw ValidationException::withMessages(['employer' => 'Your membership is suspended — contact your employer.']);
        }

        if ($existing && $existing->status !== EmployerMemberStatus::Rejected) {
            return $existing;
        }

        if ($existing) {
            $existing->update([
                'status' => EmployerMemberStatus::Pending,
                'joined_via' => EmployerJoinVia::Self,
            ]);

            return $existing->fresh();
        }

        return EmployerMember::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'status' => EmployerMemberStatus::Pending,
            'joined_via' => EmployerJoinVia::Self,
        ]);
    }

    /**
     * Form 1 acceptance — activates the membership and grants Level 1
     * workplace verification (employer confirmation = the workplace check).
     */
    public function approveMember(EmployerMember $member, User $admin): EmployerMember
    {
        if (! $member->isPending()) {
            throw ValidationException::withMessages(['member' => 'Only pending members can be approved.']);
        }

        $member->update(['status' => EmployerMemberStatus::Active]);

        $this->grantWorkplaceVerification($member->user);

        ActivityLog::log($admin, 'employer_member_approved', $member->user, null, [
            'employer_id' => $member->employer_id,
            'employee_id' => $member->employee_id,
        ]);

        return $member->fresh();
    }

    public function rejectMember(EmployerMember $member, User $admin): EmployerMember
    {
        if (! $member->isPending()) {
            throw ValidationException::withMessages(['member' => 'Only pending members can be rejected.']);
        }

        $member->update(['status' => EmployerMemberStatus::Rejected]);

        ActivityLog::log($admin, 'employer_member_rejected', $member->user, null, [
            'employer_id' => $member->employer_id,
        ]);

        return $member->fresh();
    }

    /**
     * Employer confirmation grants Level 1 (workplace verified) and, when a
     * phone number is on file, marks it verified so the employee can book
     * instantly. Never downgrades a higher level (NIN/driver stay intact).
     */
    public function grantWorkplaceVerification(User $user): void
    {
        $attributes = [];

        if ($user->verification_level->value < VerificationLevel::WorkplaceVerified->value) {
            $attributes['verification_level'] = VerificationLevel::WorkplaceVerified;
        }

        if (! $user->hasVerifiedPhone() && $user->phone) {
            $attributes['phone_verified_at'] = now();
        }

        if ($attributes) {
            $user->update($attributes);
        }
    }

    /**
     * Form 2 — enroll staff by CSV rows. Each row:
     *   email,name,phone,employee_id  (header row detected automatically)
     *   email[,name][,phone][,employee_id]  (positional fallback)
     *
     * Existing users are linked and granted Level 1; staff without an account
     * are auto-created (phone-verified + Level 1 + temporary password sent via
     * notification) so they can book immediately.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{enrolled: int, created: int, missing: int, invalid: int}
     */
    public function enrollMany(Employer $employer, array $rows): array
    {
        $enrolled = 0;
        $created = 0;
        $missing = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            $parsed = $this->parseRow($row);

            if (! $parsed['email']) {
                $invalid++;

                continue;
            }

            $user = User::where('email', $parsed['email'])->first();

            if ($user) {
                $this->grantWorkplaceVerification($user);

                EmployerMember::updateOrCreate(
                    ['employer_id' => $employer->id, 'user_id' => $user->id],
                    [
                        'employee_id' => $parsed['employee_id'],
                        'joined_via' => EmployerJoinVia::Employer,
                        'status' => EmployerMemberStatus::Active,
                    ]
                );

                $enrolled++;

                continue;
            }

            if (! $this->canAutoCreate($parsed)) {
                $missing++;

                continue;
            }

            $user = $this->createFromRoster($parsed);

            EmployerMember::create([
                'employer_id' => $employer->id,
                'user_id' => $user->id,
                'employee_id' => $parsed['employee_id'],
                'joined_via' => EmployerJoinVia::Employer,
                'status' => EmployerMemberStatus::Active,
            ]);

            $created++;
        }

        return compact('enrolled', 'created', 'missing', 'invalid');
    }

    /**
     * @param  array{email: string, name: ?string, phone: ?string, employee_id: ?string}  $parsed
     */
    private function canAutoCreate(array $parsed): bool
    {
        return filter_var($parsed['email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param  array{email: string, name: ?string, phone: ?string, employee_id: ?string}  $parsed
     */
    private function createFromRoster(array $parsed): User
    {
        $tempPassword = Str::password(12);

        $user = User::create([
            'name' => $parsed['name'] ?: Str::before($parsed['email'], '@'),
            'email' => strtolower($parsed['email']),
            'phone' => $parsed['phone'],
            'phone_verified_at' => $parsed['phone'] ? now() : null,
            'email_verified_at' => now(),
            'password' => $tempPassword,
            'verification_level' => VerificationLevel::WorkplaceVerified,
        ]);

        $user->notify(new EmployerWelcome($tempPassword));

        return $user;
    }

    /**
     * Normalise one CSV row. Detects a header row by name and maps columns
     * positionally otherwise: email, name, phone, employee_id.
     *
     * @param  array<int, string>  $row
     * @return array{email: string, name: ?string, phone: ?string, employee_id: ?string}
     */
    private function parseRow(array $row): array
    {
        $headers = array_map('strtolower', $row);

        $isHeader = (bool) array_intersect($headers, ['email', 'name', 'phone', 'employee', 'employee id', 'employee_id', 'employeeid', 'staff id', 'staff_id']);

        if ($isHeader) {
            return $this->parseByHeader($row);
        }

        $cells = array_pad($row, 4, '');
        $email = $this->extractEmail($cells);

        return [
            'email' => $email ?: '',
            'name' => $this->looksLikeName($cells[1]) ? $cells[1] : null,
            'phone' => $this->looksLikePhone($cells[2]) ? $cells[2] : null,
            'employee_id' => $this->extractEmployeeId(array_filter([$cells[3], $cells[1]], fn ($c) => $c !== '')),
        ];
    }

    /**
     * @param  array<int, string>  $row
     * @return array{email: string, name: ?string, phone: ?string, employee_id: ?string}
     */
    private function parseByHeader(array $row): array
    {
        $map = [];
        foreach ($row as $index => $header) {
            $map[strtolower(trim((string) $header))] = $index;
        }

        $cell = fn (string $key): ?string => isset($map[$key])
            ? trim((string) ($row[$map[$key]] ?? ''))
            : null;

        $email = $cell('email');
        $name = $cell('name');
        $phone = $cell('phone') ?? $cell('mobile') ?? $cell('telephone');
        $employeeId = $cell('employee_id') ?? $cell('employee') ?? $cell('employee id') ?? $cell('staff id') ?? $cell('staff_id');

        if (! $email || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $email = $this->extractEmail($row);
        }

        return [
            'email' => $email ?: '',
            'name' => $name,
            'phone' => $phone && $this->looksLikePhone($phone) ? $phone : null,
            'employee_id' => $employeeId,
        ];
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
     * @param  array<int, string>  $cells
     */
    private function extractEmployeeId(array $cells): ?string
    {
        foreach ($cells as $cell) {
            $cell = trim($cell);

            if ($cell !== '' && ! filter_var($cell, FILTER_VALIDATE_EMAIL) && ! $this->looksLikePhone($cell)) {
                return $cell;
            }
        }

        return null;
    }

    private function looksLikeName(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || $this->looksLikePhone($value) || filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // A "name" has at least one letter and spaces between words.
        return preg_match('/\p{L}.*\s.*\p{L}/u', $value) === 1;
    }

    private function looksLikePhone(string $value): bool
    {
        $digits = preg_replace('/\D/', '', trim($value));

        return strlen($digits) >= 7 && strlen($digits) <= 15;
    }

    private function enabled(): bool
    {
        return (bool) config('workride.employer_programs.enabled', false);
    }

    /**
     * @return array{0: float, 1: EmployerCoverageType}
     */
    private function policyAmount(Employer $employer, Trip $trip, float $fare): array
    {
        return match ($employer->program_type) {
            EmployerProgramType::Full => [$fare, EmployerCoverageType::Full],
            EmployerProgramType::OneWay => $this->oneWayAmount($employer, $trip, $fare),
            EmployerProgramType::Percent => [
                round($fare * (float) $employer->percent_covered / 100, 2),
                EmployerCoverageType::Percent,
            ],
            EmployerProgramType::Capped => [
                min($fare, round((float) ($employer->max_per_trip ?? 0), 2)),
                EmployerCoverageType::Capped,
            ],
        };
    }

    /**
     * @return array{0: float, 1: EmployerCoverageType}
     */
    private function oneWayAmount(Employer $employer, Trip $trip, float $fare): array
    {
        return match ($employer->covered_direction) {
            'to_work' => $this->zoneMatches($employer, $trip, 'destination')
                ? [$fare, EmployerCoverageType::OneWay]
                : [0.0, EmployerCoverageType::OneWay],
            'from_work' => $this->zoneMatches($employer, $trip, 'origin')
                ? [$fare, EmployerCoverageType::OneWay]
                : [0.0, EmployerCoverageType::OneWay],
            default => [0.0, EmployerCoverageType::OneWay],
        };
    }

    /**
     * Does the employer's zone sit at the origin or destination leg of the
     * trip's corridor? (kubwa_cbd = KUBWA → CBD, nyanya_idu = NYANYA → IDU…)
     */
    private function zoneMatches(Employer $employer, Trip $trip, string $leg): bool
    {
        $corridor = $trip->corridor?->value;
        $employerZone = strtoupper(trim((string) $employer->zone));

        if (! $corridor || $employerZone === '') {
            return false;
        }

        $zones = $leg === 'origin' ? self::ORIGIN_ZONES : self::DESTINATION_ZONES;

        return ($zones[$corridor] ?? null) === $employerZone;
    }

    private function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw ValidationException::withMessages(['employer' => 'Employer programs are not enabled.']);
        }
    }
}
