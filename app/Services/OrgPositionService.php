<?php

namespace App\Services;

use App\Enums\OrgSeatStatus;
use App\Models\OrgPosition;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class OrgPositionService
{
    public function assign(OrgPosition $seat, Person $person): OrgPosition
    {
        return DB::transaction(function () use ($seat, $person) {
            if ($person->org_position_id && (int) $person->org_position_id !== (int) $seat->id) {
                $previous = OrgPosition::query()->find($person->org_position_id);
                if ($previous) {
                    $this->release($previous, keepPerson: true);
                }
            }

            if ($seat->person_id && (int) $seat->person_id !== (int) $person->id) {
                $occupant = Person::query()->find($seat->person_id);
                if ($occupant) {
                    $occupant->forceFill(['org_position_id' => null])->save();
                }
            }

            $seat->person_id = $person->id;
            $seat->status = OrgSeatStatus::Filled;
            if ($seat->department_id === null && $person->department_id) {
                $seat->department_id = $person->department_id;
            }
            $seat->save();

            $person->forceFill([
                'org_position_id' => $seat->id,
                'job_position_id' => $seat->job_position_id,
                'department_id' => $seat->department_id ?: $person->department_id,
                'job_title' => $seat->jobPosition?->name ?: $person->job_title,
            ])->save();

            return $seat->fresh(['jobPosition', 'department', 'person']);
        });
    }

    public function release(OrgPosition $seat, bool $keepPerson = false): void
    {
        $personId = $seat->person_id;
        $seat->person_id = null;
        if ($seat->status === OrgSeatStatus::Filled) {
            $seat->status = OrgSeatStatus::Open;
        }
        $seat->save();

        if (! $keepPerson && $personId) {
            Person::query()->where('id', $personId)->where('org_position_id', $seat->id)->update(['org_position_id' => null]);
        }
    }

    public function nextSeatNo(int $organizationId, int $jobPositionId): int
    {
        $max = (int) OrgPosition::query()
            ->where('organization_id', $organizationId)
            ->where('job_position_id', $jobPositionId)
            ->max('seat_no');

        return $max + 1;
    }
}
