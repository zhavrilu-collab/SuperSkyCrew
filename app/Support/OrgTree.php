<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrgTree
{
    /**
     * @param  Collection<int, Model>  $nodes
     * @return Collection<int, Model>
     */
    public static function forest(Collection $nodes, string $childrenRelation = 'children'): Collection
    {
        $ids = $nodes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $grouped = $nodes->groupBy(function (Model $node) use ($ids) {
            $parentId = $node->parent_id ? (int) $node->parent_id : 0;

            return in_array($parentId, $ids, true) ? $parentId : 0;
        });

        $attach = function (int $parentKey) use (&$attach, $grouped, $childrenRelation): Collection {
            return ($grouped[$parentKey] ?? collect())->values()->map(function (Model $node) use ($attach, $childrenRelation) {
                $node->setRelation($childrenRelation, $attach((int) $node->id));

                return $node;
            });
        };

        return $attach(0);
    }

    /**
     * @param  Collection<int, Model>  $nodes
     * @return list<array{node: Model, depth: int}>
     */
    public static function flatten(Collection $nodes, string $childrenRelation = 'children'): array
    {
        $rows = [];
        $walk = function (Collection $items, int $depth) use (&$walk, &$rows, $childrenRelation): void {
            foreach ($items as $node) {
                $rows[] = ['node' => $node, 'depth' => $depth];
                $walk($node->{$childrenRelation}, $depth + 1);
            }
        };
        $walk(self::forest($nodes, $childrenRelation), 0);

        return $rows;
    }

    public static function wouldCycle(?int $nodeId, ?int $parentId, Collection $nodes): bool
    {
        if ($nodeId === null || $parentId === null) {
            return false;
        }
        if ($nodeId === $parentId) {
            return true;
        }

        $byId = $nodes->keyBy('id');
        $guard = 0;
        $cursor = $parentId;
        while ($cursor !== null && $guard < 50) {
            if ((int) $cursor === $nodeId) {
                return true;
            }
            $cursor = $byId->get($cursor)?->parent_id;
            $cursor = $cursor !== null ? (int) $cursor : null;
            $guard++;
        }

        return false;
    }
}
