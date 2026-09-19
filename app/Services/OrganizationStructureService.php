<?php

namespace App\Services;

use App\Models\Department;
use App\Models\EnterpriseUnit;
use App\Models\LegalEntity;
use App\Models\Organization;

class OrganizationStructureService
{
    public function ensure(Organization $organization): EnterpriseUnit
    {
        $legal = LegalEntity::query()->forOrganization($organization)->orderBy('id')->first();
        if ($legal === null) {
            $legal = LegalEntity::query()->create([
                'organization_id' => $organization->id,
                'name' => $organization->name,
                'code' => 'SJ',
                'oib' => $organization->oib,
                'city' => $organization->city,
                'country' => 'Hrvatska',
                'valid_from' => now()->toDateString(),
            ]);
        }

        $root = EnterpriseUnit::query()
            ->forOrganization($organization)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->first();

        if ($root === null) {
            $root = EnterpriseUnit::query()->create([
                'organization_id' => $organization->id,
                'legal_entity_id' => $legal->id,
                'name' => $organization->name,
                'valid_from' => now()->toDateString(),
            ]);
        }

        Department::query()
            ->forOrganization($organization)
            ->whereNull('enterprise_unit_id')
            ->update(['enterprise_unit_id' => $root->id]);

        return $root;
    }

    /**
     * @return list<int>
     */
    public function descendantUnitIds(EnterpriseUnit $unit, iterable $allUnits): array
    {
        $ids = [(int) $unit->id];
        $changed = true;
        $units = collect($allUnits);
        while ($changed) {
            $changed = false;
            foreach ($units as $candidate) {
                $id = (int) $candidate->id;
                $parentId = $candidate->parent_id ? (int) $candidate->parent_id : null;
                if ($parentId !== null && in_array($parentId, $ids, true) && ! in_array($id, $ids, true)) {
                    $ids[] = $id;
                    $changed = true;
                }
            }
        }

        return $ids;
    }
}
