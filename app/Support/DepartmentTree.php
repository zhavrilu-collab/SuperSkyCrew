<?php

namespace App\Support;

use App\Models\Department;
use Illuminate\Support\Collection;

class DepartmentTree
{
    /**
     * @param  Collection<int, Department>  $departments
     * @return Collection<int, Department>
     */
    public static function forest(Collection $departments): Collection
    {
        return OrgTree::forest($departments);
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return list<array{department: Department, depth: int}>
     */
    public static function flatten(Collection $departments): array
    {
        return array_map(
            fn (array $row) => ['department' => $row['node'], 'depth' => $row['depth']],
            OrgTree::flatten($departments),
        );
    }

    public static function wouldCycle(?int $departmentId, ?int $parentId, Collection $departments): bool
    {
        return OrgTree::wouldCycle($departmentId, $parentId, $departments);
    }
}
