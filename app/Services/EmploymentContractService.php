<?php

namespace App\Services;

use App\Enums\EmploymentInstrument;
use App\Models\EmploymentContract;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmploymentContractService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Organization $organization, Person $person, array $data, ?User $actor = null): EmploymentContract
    {
        return DB::transaction(function () use ($organization, $person, $data, $actor) {
            $current = (bool) ($data['is_current'] ?? true);
            if ($current) {
                EmploymentContract::query()
                    ->where('person_id', $person->id)
                    ->update(['is_current' => false]);
            }

            $contract = EmploymentContract::query()->create([
                'organization_id' => $organization->id,
                'person_id' => $person->id,
                'kind' => $data['kind'],
                'contract_type' => $data['contract_type'] ?? null,
                'number' => $data['number'] ?? null,
                'signed_at' => $data['signed_at'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'weekly_hours' => $data['weekly_hours'] ?? null,
                'gross_salary' => $data['gross_salary'] ?? null,
                'notice_days' => $data['notice_days'] ?? null,
                'is_current' => $current,
                'note' => $data['note'] ?? null,
                'created_by_user_id' => $actor?->id,
            ]);

            if (! $contract->number) {
                $contract->number = sprintf(
                    '%s-%d/%d',
                    strtoupper($contract->kind->value),
                    $contract->id,
                    $contract->starts_at->year,
                );
                $contract->save();
            }

            $this->syncPerson($person, $contract);

            return $contract;
        });
    }

    public function destroy(EmploymentContract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $person = $contract->person;
            $wasCurrent = $contract->is_current;
            $contract->delete();

            if (! $wasCurrent) {
                return;
            }

            $next = EmploymentContract::query()
                ->where('person_id', $person->id)
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->first();
            if ($next === null) {
                return;
            }

            $next->is_current = true;
            $next->save();
            $this->syncPerson($person->fresh(), $next);
        });
    }

    public function seedIfMissing(Person $person, ?User $actor = null): ?EmploymentContract
    {
        if (! $person->status->usesEmploymentContract()) {
            return null;
        }
        if ($person->employmentContracts()->exists()) {
            return null;
        }

        $start = $person->started_at?->toDateString() ?? now()->toDateString();

        return $this->store($person->organization, $person, [
            'kind' => EmploymentInstrument::EmploymentContract->value,
            'contract_type' => $person->contract_type?->value,
            'signed_at' => $start,
            'starts_at' => $start,
            'ends_at' => $person->contract_type?->value === 'fixed_term' ? $person->ended_at?->toDateString() : null,
            'weekly_hours' => 40,
            'is_current' => true,
        ], $actor);
    }

    private function syncPerson(Person $person, EmploymentContract $contract): void
    {
        if (! $contract->is_current) {
            return;
        }

        $updates = [];
        if ($contract->contract_type) {
            $updates['contract_type'] = $contract->contract_type;
        }
        if ($contract->kind === EmploymentInstrument::EmploymentContract && $person->started_at === null) {
            $updates['started_at'] = $contract->starts_at->toDateString();
        }
        if ($updates !== []) {
            $person->update($updates);
        }
    }
}
