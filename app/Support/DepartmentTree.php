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
        $ids = $departments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $grouped = $departments->groupBy(function (Department $department) use ($ids) {
            $parentId = $department->parent_id ? (int) $department->parent_id : 0;

            return in_array($parentId, $ids, true) ? $parentId : 0;
        });

        $attach = function (int $parentKey) use (&$attach, $grouped): Collection {
            return ($grouped[$parentKey] ?? collect())->values()->map(function (Department $department) use ($attach) {
                $department->setRelation('children', $attach((int) $department->id));

                return $department;
            });
        };

        return $attach(0);
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return list<array{department: Department, depth: int}>
     */
    public static function flatten(Collection $departments): array
    {
        $rows = [];
        $walk = function (Collection $nodes, int $depth) use (&$walk, &$rows): void {
            foreach ($nodes as $department) {
                $rows[] = ['department' => $department, 'depth' => $depth];
                $walk($department->children, $depth + 1);
            }
        };
        $walk(self::forest($departments), 0);

        return $rows;
    }

    public static function wouldCycle(?int $departmentId, ?int $parentId, Collection $departments): bool
    {
        if ($departmentId === null || $parentId === null) {
            return false;
        }
        if ($departmentId === $parentId) {
            return true;
        }

        $byId = $departments->keyBy('id');
        $guard = 0;
        $cursor = $parentId;
        while ($cursor !== null && $guard < 50) {
            if ((int) $cursor === $departmentId) {
                return true;
            }
            $cursor = $byId->get($cursor)?->parent_id;
            $cursor = $cursor !== null ? (int) $cursor : null;
            $guard++;
        }

        return false;
    }
}
