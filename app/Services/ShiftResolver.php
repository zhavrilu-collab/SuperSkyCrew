<?php

namespace App\Services;

use App\Enums\CalendarLevel;
use App\Models\CalendarRule;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Shift;
use App\Models\ShiftOverride;
use App\Support\CroatianHolidays;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftResolver
{
    public const LATE_GRACE_MINUTES = 5;

    /**
     * @var array<int, Collection<int, CalendarRule>>
     */
    private array $rulesByOrg = [];

    /**
     * @var array<int, Collection<int, Collection<int, ShiftOverride>>>
     */
    private array $overridesByOrg = [];

    public function lateGraceMinutes(Person $person): int
    {
        $person->loadMissing('location');
        $grace = $person->location?->punch_grace_minutes;
        if ($grace === null) {
            return self::LATE_GRACE_MINUTES;
        }

        return max(0, (int) $grace);
    }

    public function forPersonOn(Person $person, Carbon $date): ?Shift
    {
        $override = $this->overrideFor($person, $date);
        if ($override !== null) {
            return $override->shift;
        }
        $person->loadMissing(['department', 'jobPosition']);
        $weekday = $date->isoWeekday();
        $rules = $this->rulesFor($person->organization_id)
            ->filter(fn (CalendarRule $rule) => (int) $rule->weekday === $weekday && $rule->isValidOn($date));

        $personRule = $rules->first(
            fn (CalendarRule $rule) => $rule->level === CalendarLevel::Person
                && (int) $rule->person_id === (int) $person->id
        );

        if (CroatianHolidays::isHoliday($date)) {
            return $personRule?->shift;
        }

        $jobRule = $person->job_position_id
            ? $rules->first(
                fn (CalendarRule $rule) => $rule->level === CalendarLevel::JobPosition
                    && (int) $rule->job_position_id === (int) $person->job_position_id
            )
            : null;

        $deptRule = $person->department_id
            ? $rules->first(
                fn (CalendarRule $rule) => $rule->level === CalendarLevel::Department
                    && (int) $rule->department_id === (int) $person->department_id
            )
            : null;

        $orgRule = $rules->first(fn (CalendarRule $rule) => $rule->level === CalendarLevel::Organization);

        $chosen = $personRule ?? $jobRule ?? $deptRule ?? $orgRule;

        return $chosen?->shift;
    }

    /**
     * @param  Collection<int, Person>  $people
     * @return array<int, array<string, Shift|null>>
     */
    public function mapForPeople(Collection $people, Carbon $from, Carbon $to): array
    {
        $map = [];
        foreach ($people as $person) {
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $map[$person->id][$day->toDateString()] = $this->forPersonOn($person, $day->copy());
            }
        }

        return $map;
    }

    /**
     * @return Collection<int, CalendarRule>
     */
    private function rulesFor(int $organizationId): Collection
    {
        if (! isset($this->rulesByOrg[$organizationId])) {
            $this->rulesByOrg[$organizationId] = CalendarRule::query()
                ->where('organization_id', $organizationId)
                ->with(['shift', 'department', 'jobPosition', 'person'])
                ->orderBy('id')
                ->get();
        }

        return $this->rulesByOrg[$organizationId];
    }

    private function overrideFor(Person $person, Carbon $date): ?ShiftOverride
    {
        $organizationId = (int) $person->organization_id;
        if (! isset($this->overridesByOrg[$organizationId])) {
            $this->overridesByOrg[$organizationId] = ShiftOverride::query()
                ->where('organization_id', $organizationId)
                ->with('shift')
                ->get()
                ->groupBy('person_id');
        }

        $list = $this->overridesByOrg[$organizationId]->get($person->id, collect());

        return $list->first(
            fn (ShiftOverride $override) => $override->work_date->toDateString() === $date->toDateString()
        );
    }
}
