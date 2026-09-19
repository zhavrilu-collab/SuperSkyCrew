<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\TimeEntryStatus;
use App\Models\Organization;
use App\Models\PeriodLock;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PeriodLockService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}
    public function isLocked(int $organizationId, CarbonInterface|string $date): bool
    {
        $day = Carbon::parse($date);

        return PeriodLock::query()
            ->where('organization_id', $organizationId)
            ->where('year', (int) $day->year)
            ->where('month', (int) $day->month)
            ->exists();
    }

    public function assertWritable(Person $person, CarbonInterface|string $date): void
    {
        $day = Carbon::parse($date);

        if ($this->isLocked($person->organization_id, $day)) {
            throw ValidationException::withMessages([
                'occurred_at' => 'Razdoblje '.$day->format('m/Y').' je zaključano.',
            ]);
        }

        $entry = TimeEntry::query()
            ->where('person_id', $person->id)
            ->whereDate('work_date', $day->toDateString())
            ->first();

        if ($entry?->isLocked()) {
            throw ValidationException::withMessages([
                'occurred_at' => 'Slog za '.$day->toDateString().' je zaključan.',
            ]);
        }
    }

    public function lock(Organization $organization, int $year, int $month, ?User $actor): PeriodLock
    {
        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages(['month' => 'Neispravan mjesec.']);
        }

        $existing = PeriodLock::query()
            ->where('organization_id', $organization->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'month' => 'To razdoblje je već zaključano.',
            ]);
        }

        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth();

        TimeEntry::query()
            ->where('organization_id', $organization->id)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->update(['status' => TimeEntryStatus::Locked->value]);

        $lock = PeriodLock::query()->create([
            'organization_id' => $organization->id,
            'year' => $year,
            'month' => $month,
            'locked_by_user_id' => $actor?->id,
            'locked_at' => now(),
        ]);

        $this->audit->record(
            $organization,
            AuditAction::PeriodLock,
            $actor,
            ($actor === null ? 'Automatski zaključano razdoblje ' : 'Zaključano razdoblje ').$lock->label(),
            null,
            PeriodLock::class,
            $lock->id,
            ['year' => $year, 'month' => $month, 'automatic' => $actor === null],
        );

        return $lock;
    }

    public function lockPreviousIfDue(Organization $organization, ?CarbonInterface $now = null): ?PeriodLock
    {
        $day = (int) ($organization->period_lock_day ?? 0);
        if ($day < 1 || $day > 28) {
            return null;
        }

        $today = Carbon::parse(($now ?? now())->toDateString(), config('app.timezone'))->startOfDay();
        if ((int) $today->day < $day) {
            return null;
        }

        $previous = $today->copy()->startOfMonth()->subMonth();
        if ($this->forMonth($organization, (int) $previous->year, (int) $previous->month) !== null) {
            return null;
        }

        return $this->lock($organization, (int) $previous->year, (int) $previous->month, null);
    }

    public function forMonth(Organization $organization, int $year, int $month): ?PeriodLock
    {
        return PeriodLock::query()
            ->where('organization_id', $organization->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
