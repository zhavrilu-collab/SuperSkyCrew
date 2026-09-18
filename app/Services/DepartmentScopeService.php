<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Organization;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DepartmentScopeService
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function seesAllPeople(int $organizationId, int $userId): bool
    {
        return $this->rbac->can($organizationId, $userId, 'people.access')
            || $this->rbac->can($organizationId, $userId, 'payroll.export');
    }

    /**
     * @param  Builder<Person>  $query
     * @return Builder<Person>
     */
    public function restrictPeopleQuery(Builder $query, Organization $organization, int $userId): Builder
    {
        if ($this->seesAllPeople($organization->id, $userId)) {
            return $query;
        }

        if ($this->rbac->roleForUser($organization->id, $userId) !== OrganizationRole::Manager) {
            return $query->where('people.user_id', $userId);
        }

        if (! Department::query()->forOrganization($organization)->exists()) {
            return $query;
        }

        $departmentIds = $this->managedDepartmentIds($organization, $userId);

        return $query->where(function (Builder $inner) use ($departmentIds, $userId) {
            $inner->whereIn('people.department_id', $departmentIds)
                ->orWhere('people.manager_user_id', $userId);
        });
    }

    /**
     * @return list<int>
     */
    public function managedDepartmentIds(Organization $organization, int $userId): array
    {
        return Department::query()
            ->forOrganization($organization)
            ->where('manager_user_id', $userId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function canManagePerson(Person $person, int $userId): bool
    {
        if ((int) $person->user_id === $userId) {
            return true;
        }

        if ($this->seesAllPeople($person->organization_id, $userId)) {
            return true;
        }

        if ($this->rbac->roleForUser($person->organization_id, $userId) !== OrganizationRole::Manager) {
            return (int) $person->user_id === $userId;
        }

        $organization = $person->organization ?? Organization::query()->find($person->organization_id);
        if ($organization === null) {
            return false;
        }

        if (! Department::query()->forOrganization($organization)->exists()) {
            return true;
        }

        if ((int) $person->manager_user_id === $userId) {
            return true;
        }

        return $person->department_id !== null
            && in_array((int) $person->department_id, $this->managedDepartmentIds($organization, $userId), true);
    }

    /**
     * @return Collection<int, Department>
     */
    public function departmentsOn(Organization $organization, Carbon $date): Collection
    {
        $day = $date->toDateString();

        return Department::query()
            ->forOrganization($organization)
            ->with('manager')
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day);
            })
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $day);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, JobPosition>
     */
    public function positionsOn(Organization $organization, Carbon $date): Collection
    {
        $day = $date->toDateString();

        return JobPosition::query()
            ->forOrganization($organization)
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day);
            })
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $day);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, CostCenter>
     */
    public function costCentersOn(Organization $organization, Carbon $date): Collection
    {
        $day = $date->toDateString();

        return CostCenter::query()
            ->forOrganization($organization)
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day);
            })
            ->where(function (Builder $query) use ($day) {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $day);
            })
            ->orderBy('code')
            ->get();
    }
}
