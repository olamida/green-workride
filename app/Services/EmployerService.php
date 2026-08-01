<?php

namespace App\Services;

use App\Enums\EmployerCoverageType;
use App\Enums\EmployerMemberStatus;
use App\Enums\EmployerProgramType;
use App\Models\Booking;
use App\Models\Employer;
use App\Models\EmployerMember;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Carbon;
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
     * Enroll staff by CSV rows. Each row: email[, employee_id].
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{enrolled: int, missing: int, invalid: int}
     */
    public function enrollMany(Employer $employer, array $rows): array
    {
        $enrolled = 0;
        $missing = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            $email = $this->extractEmail($row);
            $employeeId = $this->extractEmployeeId($row);

            if (! $email) {
                $invalid++;

                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $missing++;

                continue;
            }

            EmployerMember::updateOrCreate(
                ['employer_id' => $employer->id, 'user_id' => $user->id],
                [
                    'employee_id' => $employeeId,
                    'status' => EmployerMemberStatus::Active->value,
                ]
            );

            $enrolled++;
        }

        return compact('enrolled', 'missing', 'invalid');
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
    private function extractEmployeeId(array $row): ?string
    {
        foreach ($row as $cell) {
            if (! filter_var($cell, FILTER_VALIDATE_EMAIL) && trim($cell) !== '') {
                return $cell;
            }
        }

        return null;
    }
}
