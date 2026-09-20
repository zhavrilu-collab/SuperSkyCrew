<?php

namespace App\Services;

use App\Enums\PersonStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveTenureRule;
use App\Models\Organization;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function ensureBalance(Person $person, ?int $year = null): LeaveBalance
    {
        $year ??= $this->currentYear();
        $breakdown = $this->breakdown($person, $year);

        $balance = LeaveBalance::query()->firstOrNew([
            'person_id' => $person->id,
            'year' => $year,
        ]);

        if (! $balance->exists) {
            $balance->organization_id = $person->organization_id;
            $balance->carried_days = $this->carryFromPrevious($person, $year);
            $balance->used_days = 0;
        }

        $balance->entitled_days = $breakdown['entitled'];
        $balance->save();

        return $balance;
    }

    /**
     * @return array{year: int, entitled: int, carried: int, used: int, remaining_old: int, remaining_new: int, remaining: int, tenure_years: int, calculated: int, manual: bool, parts: array<string, int|bool>}
     */
    public function snapshot(Person $person, ?int $year = null): array
    {
        $year ??= $this->currentYear();
        $breakdown = $this->breakdown($person, $year);
        $balance = $this->ensureBalance($person, $year);

        return [
            'year' => $year,
            'entitled' => (int) $balance->entitled_days,
            'carried' => (int) $balance->carried_days,
            'used' => (int) $balance->used_days,
            'remaining_old' => $balance->remainingOld(),
            'remaining_new' => $balance->remainingNew(),
            'remaining' => $balance->remaining(),
            'tenure_years' => $breakdown['tenure_years'],
            'calculated' => $breakdown['calculated'],
            'manual' => $breakdown['manual'],
            'parts' => $breakdown,
        ];
    }

    /**
     * @return array{year: int, base: int, position: int, tenure_months: int, tenure_years: int, tenure_extra: int, children: int, children_extra: int, calculated: int, manual: bool, entitled: int}
     */
    public function breakdown(Person $person, ?int $year = null): array
    {
        $year ??= $this->currentYear();
        $person->loadMissing(['organization', 'jobPosition']);
        $organization = $person->organization ?? $person->organization()->first();

        $base = (int) ($organization?->annual_leave_base_days ?: 20);
        $positionDays = (int) ($person->jobPosition?->annual_leave_days ?: 0);
        $tenureMonths = $this->tenureMonths($person, $year);
        $tenureYears = intdiv($tenureMonths, 12);
        $tenureExtra = $this->tenureExtra($organization, $tenureYears);
        $children = max(0, (int) ($person->children_count ?? 0));
        $perChild = (int) ($organization?->annual_leave_days_per_child ?: 0);
        $childrenExtra = $children * $perChild;
        $calculated = min(50, max($base, $positionDays) + $tenureExtra + $childrenExtra);
        $manual = (bool) $person->annual_leave_manual;
        $entitled = $manual
            ? (int) ($person->annual_leave_days ?: $calculated)
            : $calculated;

        return [
            'year' => $year,
            'base' => $base,
            'position' => $positionDays,
            'tenure_months' => $tenureMonths,
            'tenure_years' => $tenureYears,
            'tenure_extra' => $tenureExtra,
            'children' => $children,
            'children_extra' => $childrenExtra,
            'calculated' => $calculated,
            'manual' => $manual,
            'entitled' => $entitled,
        ];
    }

    /**
     * @return array{employer_months: int, prior_months: int, total_months: int, total_label: string, retirement_years: int|null, retirement_date: string|null}
     */
    public function serviceCard(Person $person, ?int $year = null): array
    {
        $total = $this->tenureMonths($person, $year);
        $prior = (int) ($person->prior_service_months ?? 0);
        $employer = max(0, $total - $prior);
        $retirement = null;
        $yearsLeft = null;
        if ($person->date_of_birth) {
            $now = now()->timezone(config('app.timezone'))->startOfDay();
            $target = $person->date_of_birth->copy()->addYears(65);
            $retirement = $target->format('d.m.Y.');
            $yearsLeft = $now->lt($target) ? (int) $now->diffInYears($target) : 0;
        }

        return [
            'employer_months' => $employer,
            'prior_months' => $prior,
            'total_months' => $total,
            'total_label' => intdiv($total, 12).' g. '.($total % 12).' mj.',
            'retirement_years' => $yearsLeft,
            'retirement_date' => $retirement,
        ];
    }

    public function tenureMonths(Person $person, ?int $year = null): int
    {
        $year ??= $this->currentYear();
        $asOf = Carbon::create($year, 12, 31, 0, 0, 0, config('app.timezone'))->startOfDay();
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        if ($asOf->gt($today)) {
            $asOf = $today;
        }

        $prior = (int) ($person->prior_service_months ?? 0);
        if ($person->started_at === null) {
            return $prior;
        }

        $start = $person->started_at->copy()->startOfDay();
        if ($start->gt($asOf)) {
            return $prior;
        }

        $months = ($asOf->year - $start->year) * 12 + ($asOf->month - $start->month);
        if ($asOf->day < $start->day) {
            $months--;
        }

        return $prior + max(0, $months);
    }

    public function applyToPerson(Person $person, ?int $year = null): ?LeaveBalance
    {
        if (! $person->status->usesArticleThree()) {
            return null;
        }

        $year ??= $this->currentYear();
        $breakdown = $this->breakdown($person, $year);

        if (! $person->annual_leave_manual) {
            $person->forceFill(['annual_leave_days' => $breakdown['calculated']])->save();
        }

        return $this->ensureBalance($person->fresh(['organization', 'jobPosition']), $year);
    }

    public function recalculateOrganization(Organization $organization, ?int $year = null): int
    {
        $year ??= $this->currentYear();
        $count = 0;

        Person::query()
            ->forOrganization($organization)
            ->with(['organization', 'jobPosition'])
            ->whereIn('status', [
                PersonStatus::Employee->value,
                PersonStatus::Assigned->value,
                PersonStatus::Executive->value,
            ])
            ->where('annual_leave_manual', false)
            ->orderBy('id')
            ->get()
            ->each(function (Person $person) use ($year, &$count): void {
                $this->applyToPerson($person, $year);
                $count++;
            });

        return $count;
    }

    public function assertAvailable(Person $person, int $days, int $year): void
    {
        $balance = $this->ensureBalance($person, $year);

        if ($days > $balance->remaining()) {
            throw ValidationException::withMessages([
                'to' => 'Nema dovoljno dana GO (preostalo '.$balance->remaining().', traženo '.$days.').',
            ]);
        }
    }

    public function consume(Person $person, int $days, int $year): LeaveBalance
    {
        $this->assertAvailable($person, $days, $year);
        $balance = $this->ensureBalance($person, $year);
        $balance->used_days += $days;
        $balance->save();

        return $balance;
    }

    private function tenureExtra(?Organization $organization, int $tenureYears): int
    {
        if ($organization === null) {
            return 0;
        }

        $rule = LeaveTenureRule::query()
            ->where('organization_id', $organization->id)
            ->where('min_years', '<=', $tenureYears)
            ->orderByDesc('min_years')
            ->first();

        return (int) ($rule?->extra_days ?: 0);
    }

    private function carryFromPrevious(Person $person, int $year): int
    {
        $previous = LeaveBalance::query()
            ->where('person_id', $person->id)
            ->where('year', $year - 1)
            ->first();

        return $previous ? $previous->remaining() : 0;
    }

    private function currentYear(): int
    {
        return (int) now()->timezone(config('app.timezone'))->year;
    }
}
