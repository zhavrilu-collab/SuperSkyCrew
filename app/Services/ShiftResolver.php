<?php

namespace App\Services;

use App\Enums\CalendarLevel;
use App\Models\CalendarRule;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Shift;
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

    public function forPersonOn(Person $person, Carbon $date): ?Shift
    {
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
}
