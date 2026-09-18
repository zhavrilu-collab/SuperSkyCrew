<?php

namespace App\Services;

use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PeopleRegisterService
{
    /**
     * @return list<string>
     */
    public function registerStatuses(): array
    {
        return [
            PersonStatus::Employee->value,
            PersonStatus::Assigned->value,
            PersonStatus::OtherFo->value,
            PersonStatus::Contractor->value,
            PersonStatus::Executive->value,
            PersonStatus::Former->value,
        ];
    }

    /**
     * @return Collection<int, Person>
     */
    public function activeOn(Organization $organization, Carbon $on): Collection
    {
        $day = $on->toDateString();

        return $this->baseQuery($organization)
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('started_at')->orWhereDate('started_at', '<=', $day);
            })
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $day);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return Collection<int, Person>
     */
    public function hires(Organization $organization, Carbon $from, Carbon $to): Collection
    {
        return $this->baseQuery($organization)
            ->whereNotNull('started_at')
            ->whereDate('started_at', '>=', $from->toDateString())
            ->whereDate('started_at', '<=', $to->toDateString())
            ->orderBy('started_at')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return Collection<int, Person>
     */
    public function exits(Organization $organization, Carbon $from, Carbon $to): Collection
    {
        return $this->baseQuery($organization)
            ->whereNotNull('ended_at')
            ->whereDate('ended_at', '>=', $from->toDateString())
            ->whereDate('ended_at', '<=', $to->toDateString())
            ->orderBy('ended_at')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function csvHeaders(): array
    {
        return [
            'Rbr', 'Prezime', 'Ime', 'OIB', 'Spol', 'Datum rođenja', 'Državljanstvo',
            'Prebivalište', 'Status', 'Radno mjesto', 'Odjel', 'Mjesto troška', 'Lokacija',
            'Vrsta ugovora', 'Početak', 'Prestanak', 'Prijava na osiguranja',
        ];
    }

    /**
     * @return list<string>
     */
    public function payrollHeaders(): array
    {
        return [
            'Prezime', 'Ime', 'OIB', 'IBAN', 'Koeficijent', 'Dodaci %',
            'Staž prije (mj.)', 'Djeca (GO)', 'Uzdržavani', 'Olakšica',
            'Obiteljsko pravo', 'ZNR pregled obavezan', 'Radno mjesto', 'Mjesto troška',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function payrollRows(Collection $people): array
    {
        $rows = [];
        foreach ($people as $person) {
            $rows[] = [
                $person->last_name,
                $person->first_name,
                $person->oib ?: '',
                $person->iban ?: '',
                $person->pay_coefficient !== null ? (string) $person->pay_coefficient : '',
                $person->allowance_percent !== null ? (string) $person->allowance_percent : '',
                $person->prior_service_months !== null ? (string) $person->prior_service_months : '',
                (string) ($person->children_count ?? 0),
                (string) ($person->dependents_count ?? 0),
                $person->tax_relief_note ?: '',
                $person->family_right?->label() ?: '',
                $person->znr_exam_required ? 'da' : 'ne',
                $person->jobLabel(),
                $person->costCenter?->summary() ?: '',
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    public function csvRows(Collection $people): array
    {
        $rows = [];
        $i = 1;
        foreach ($people as $person) {
            $rows[] = [
                (string) $i++,
                $person->last_name,
                $person->first_name,
                $person->oib ?: '',
                $person->genderLabel(),
                $person->date_of_birth?->format('d.m.Y.') ?: '',
                $person->citizenship ?: '',
                $person->residence ?: '',
                $person->status->label(),
                $person->jobLabel(),
                $person->department?->name ?: '',
                $person->costCenter?->summary() ?: '',
                $person->location?->name ?: '',
                $person->contract_type?->label() ?: '',
                $person->started_at?->format('d.m.Y.') ?: '',
                $person->ended_at?->format('d.m.Y.') ?: '',
                $person->insurance_filed_at?->format('d.m.Y.') ?: '',
            ];
        }

        return $rows;
    }

    /**
     * @return Builder<Person>
     */
    private function baseQuery(Organization $organization): Builder
    {
        return Person::query()
            ->forOrganization($organization)
            ->with(['department', 'jobPosition', 'location', 'costCenter'])
            ->whereIn('status', $this->registerStatuses());
    }
}
