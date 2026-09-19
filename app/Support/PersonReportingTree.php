<?php

namespace App\Support;

use App\Models\Person;
use Illuminate\Support\Collection;

class PersonReportingTree
{
    /**
     * @param  Collection<int, Person>  $people
     * @return Collection<int, Person>
     */
    public static function forest(Collection $people): Collection
    {
        $ids = $people->pluck('id')->map(fn ($id) => (int) $id)->all();
        $byUserId = $people->filter(fn (Person $person) => $person->user_id)->keyBy(fn (Person $person) => (int) $person->user_id);

        $grouped = $people->groupBy(function (Person $person) use ($byUserId, $ids) {
            $manager = $person->manager_user_id ? $byUserId->get((int) $person->manager_user_id) : null;
            if ($manager === null || (int) $manager->id === (int) $person->id || ! in_array((int) $manager->id, $ids, true)) {
                return 0;
            }

            return (int) $manager->id;
        });

        $attach = function (int $parentKey) use (&$attach, $grouped): Collection {
            return ($grouped[$parentKey] ?? collect())->values()->map(function (Person $person) use ($attach) {
                $person->setRelation('reports', $attach((int) $person->id));

                return $person;
            });
        };

        return $attach(0);
    }
}
