<?php

namespace App\Services;

use App\Models\TimeEntry;
use Illuminate\Support\Collection;

class PayrollHoursService
{
    /**
     * @param  Collection<int, TimeEntry>  $entries
     * @return list<array<string, mixed>>
     */
    public function rows(Collection $entries): array
    {
        return $entries
            ->filter(fn (TimeEntry $entry) => (int) $entry->evidential_minutes > 0)
            ->groupBy(function (TimeEntry $entry) {
                $code = $entry->evidential_code ?: ($entry->absence_code ?: 'RD');
                $costCenterId = $entry->evidential_cost_center_id
                    ?: ($entry->person?->cost_center_id ?: 0);

                return $entry->person_id.'|'.$code.'|'.$costCenterId;
            })
            ->map(function (Collection $group) {
                /** @var TimeEntry $entry */
                $entry = $group->first();
                $person = $entry->person;
                $costCenter = $entry->evidentialCostCenter ?: $person?->costCenter;
                $minutes = (int) $group->sum('evidential_minutes');

                return [
                    'person_id' => $entry->person_id,
                    'name' => $person?->fullName() ?? '',
                    'oib' => $person?->oib ?? '',
                    'code' => $entry->evidential_code ?: ($entry->absence_code ?: 'RD'),
                    'cost_center' => $costCenter?->summary() ?? '',
                    'minutes' => $minutes,
                    'hours' => round($minutes / 60, 2),
                ];
            })
            ->sortBy(fn (array $row) => $row['name'].'|'.$row['code'])
            ->values()
            ->all();
    }
}
