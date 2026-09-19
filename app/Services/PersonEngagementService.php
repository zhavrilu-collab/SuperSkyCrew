<?php

namespace App\Services;

use App\Enums\PersonStatus;
use App\Models\Person;
use App\Models\PersonEngagement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PersonEngagementService
{
    /** @var list<string> */
    public const TRACKED = [
        'status',
        'department_id',
        'job_position_id',
        'location_id',
        'cost_center_id',
        'manager_user_id',
        'job_title',
    ];

    public function sync(Person $person, ?User $actor = null): ?PersonEngagement
    {
        $person->refresh();
        $snapshot = $this->snapshot($person);
        $today = Carbon::now(config('app.timezone'))->startOfDay();
        $actorId = $actor?->id ?? Auth::id();

        $current = $person->engagements()
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->first();

        if ($current === null) {
            $from = $person->started_at?->copy()->startOfDay() ?? $today;
            $to = $this->closeOn($person);

            return $person->engagements()->create($snapshot + [
                'organization_id' => $person->organization_id,
                'valid_from' => $from->toDateString(),
                'valid_to' => $to?->toDateString(),
                'changed_by' => $actorId,
            ]);
        }

        if ($this->matches($current, $snapshot) && $current->valid_to?->toDateString() === $this->closeOn($person)?->toDateString()) {
            return $current;
        }

        $from = $today;
        if ($current->valid_from->toDateString() >= $from->toDateString()) {
            $current->fill($snapshot + [
                'valid_to' => $this->closeOn($person)?->toDateString(),
                'changed_by' => $actorId,
            ]);
            $current->save();

            return $current;
        }

        $close = $from->copy()->subDay();
        if ($close->lt($current->valid_from)) {
            $close = $current->valid_from->copy();
        }
        $ended = $this->closeOn($person);
        if ($ended && $ended->lt($close)) {
            $close = $ended;
        }

        $current->update([
            'valid_to' => $close->toDateString(),
        ]);

        $nextFrom = $close->copy()->addDay();
        if ($person->status === PersonStatus::Former && $ended && $nextFrom->gt($ended)) {
            return $current;
        }

        return $person->engagements()->create($snapshot + [
            'organization_id' => $person->organization_id,
            'valid_from' => $nextFrom->toDateString(),
            'valid_to' => $this->closeOn($person)?->toDateString(),
            'changed_by' => $actorId,
        ]);
    }

    public function forPersonOn(Person $person, Carbon $on): ?PersonEngagement
    {
        $rows = $person->relationLoaded('engagements')
            ? $person->engagements
            : $person->engagements()->with(['department', 'jobPosition', 'location', 'costCenter'])->get();

        return $rows
            ->sortByDesc(fn (PersonEngagement $row) => $row->valid_from?->toDateString().'-'.$row->id)
            ->first(fn (PersonEngagement $row) => $row->covers($on));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Person $person): array
    {
        return [
            'status' => $person->status,
            'department_id' => $person->department_id,
            'job_position_id' => $person->job_position_id,
            'location_id' => $person->location_id,
            'cost_center_id' => $person->cost_center_id,
            'manager_user_id' => $person->manager_user_id,
            'job_title' => $person->job_title,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function matches(PersonEngagement $row, array $snapshot): bool
    {
        return (string) $row->status->value === (string) ($snapshot['status'] instanceof PersonStatus ? $snapshot['status']->value : $snapshot['status'])
            && (int) ($row->department_id ?? 0) === (int) ($snapshot['department_id'] ?? 0)
            && (int) ($row->job_position_id ?? 0) === (int) ($snapshot['job_position_id'] ?? 0)
            && (int) ($row->location_id ?? 0) === (int) ($snapshot['location_id'] ?? 0)
            && (int) ($row->cost_center_id ?? 0) === (int) ($snapshot['cost_center_id'] ?? 0)
            && (int) ($row->manager_user_id ?? 0) === (int) ($snapshot['manager_user_id'] ?? 0)
            && (string) ($row->job_title ?? '') === (string) ($snapshot['job_title'] ?? '');
    }

    private function closeOn(Person $person): ?Carbon
    {
        if ($person->status !== PersonStatus::Former || $person->ended_at === null) {
            return null;
        }

        return $person->ended_at->copy()->startOfDay();
    }
}
