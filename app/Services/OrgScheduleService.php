<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\Department;
use App\Models\EnterpriseUnit;
use App\Models\JobPosition;
use App\Models\LegalEntity;
use App\Models\Organization;
use App\Models\OrgPosition;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrgScheduleService
{
    /**
     * @return list<array{type: string, name: string, from: string, to: string|null}>
     */
    public function upcoming(Organization $organization, ?Carbon $today = null): array
    {
        $today ??= now()->timezone(config('app.timezone'))->startOfDay();
        $day = $today->toDateString();
        $rows = [];

        $push = function (Collection $items, string $type, callable $name) use (&$rows, $day): void {
            foreach ($items as $item) {
                $from = $item->valid_from?->toDateString();
                if ($from === null || $from <= $day) {
                    continue;
                }
                $rows[] = [
                    'type' => $type,
                    'name' => $name($item),
                    'from' => $from,
                    'to' => $item->valid_to?->toDateString(),
                ];
            }
        };

        $named = fn ($item) => (string) ($item->name ?: $item->id);
        $push(LegalEntity::query()->forOrganization($organization)->get(), 'Članica grupacije', $named);
        $push(WorkCenter::query()->forOrganization($organization)->get(), 'Poslovnica', $named);
        $push(CostCenter::query()->forOrganization($organization)->get(), 'Mjesto troška', $named);
        $push(EnterpriseUnit::query()->forOrganization($organization)->get(), 'Poslovni ustroj', $named);
        $push(Department::query()->forOrganization($organization)->get(), 'Odjel', $named);
        $push(JobPosition::query()->forOrganization($organization)->get(), 'Radno mjesto', $named);
        $push(
            OrgPosition::query()->forOrganization($organization)->with(['jobPosition', 'department'])->get(),
            'Radna pozicija',
            fn (OrgPosition $seat) => $seat->label(),
        );

        usort($rows, fn ($a, $b) => strcmp($a['from'], $b['from']));

        return $rows;
    }
}
