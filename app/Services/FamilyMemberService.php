<?php

namespace App\Services;

use App\Enums\FamilyKin;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonFamilyMember;

class FamilyMemberService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Organization $organization, Person $person, array $data): PersonFamilyMember
    {
        $member = PersonFamilyMember::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'kin' => $data['kin'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'is_dependent' => (bool) ($data['is_dependent'] ?? false),
            'is_emergency_contact' => (bool) ($data['is_emergency_contact'] ?? false),
            'phone' => $data['phone'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $this->syncCounts($person);

        return $member;
    }

    public function destroy(PersonFamilyMember $member): void
    {
        $person = $member->person;
        $member->delete();
        if ($person) {
            $this->syncCounts($person);
        }
    }

    public function syncCounts(Person $person): void
    {
        $members = PersonFamilyMember::query()->where('person_id', $person->id)->get();
        $person->forceFill([
            'children_count' => $members->where('kin', FamilyKin::Child)->count(),
            'dependents_count' => $members->where('is_dependent', true)->count(),
        ])->save();
    }
}
