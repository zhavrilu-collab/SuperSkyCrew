<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\OrganizationUser;

class OrganizationRbacService
{
    /** @var array<string, list<OrganizationRole>> */
    private const PERMISSIONS = [
        'team.manage' => [OrganizationRole::Owner, OrganizationRole::Hr],
        'settings.manage' => [OrganizationRole::Owner],
        'people.access' => [OrganizationRole::Owner, OrganizationRole::Hr],
        'time.access' => [OrganizationRole::Owner, OrganizationRole::Hr, OrganizationRole::Manager],
        'payroll.export' => [OrganizationRole::Owner, OrganizationRole::Hr, OrganizationRole::Accountant],
        'time.lock' => [OrganizationRole::Owner, OrganizationRole::Hr],
        'inspection.export' => [OrganizationRole::Owner, OrganizationRole::Hr],
        'requests.submit' => [
            OrganizationRole::Owner,
            OrganizationRole::Hr,
            OrganizationRole::Accountant,
            OrganizationRole::Manager,
            OrganizationRole::Employee,
        ],
        'requests.approve' => [OrganizationRole::Owner, OrganizationRole::Hr, OrganizationRole::Manager],
        'dashboard.view' => [
            OrganizationRole::Owner,
            OrganizationRole::Hr,
            OrganizationRole::Accountant,
            OrganizationRole::Manager,
            OrganizationRole::Employee,
        ],
    ];

    public function roleForUser(int $organizationId, int $userId): ?OrganizationRole
    {
        $membership = OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->first();

        return $membership?->role;
    }

    public function can(int $organizationId, int $userId, string $permission): bool
    {
        $role = $this->roleForUser($organizationId, $userId);

        if ($role === null) {
            return false;
        }

        $allowed = self::PERMISSIONS[$permission] ?? [];

        return in_array($role, $allowed, true);
    }

    public function authorize(int $organizationId, int $userId, string $permission): void
    {
        if (! $this->can($organizationId, $userId, $permission)) {
            abort(403, 'Nemate ovlasti za ovu radnju.');
        }
    }

    public function isOwner(int $organizationId, int $userId): bool
    {
        return $this->roleForUser($organizationId, $userId) === OrganizationRole::Owner;
    }

    public function canOpenSettings(int $organizationId, int $userId): bool
    {
        return $this->can($organizationId, $userId, 'settings.manage')
            || $this->can($organizationId, $userId, 'people.access')
            || $this->can($organizationId, $userId, 'team.manage');
    }

    public function canAccessSettingsTab(int $organizationId, int $userId, string $tab): bool
    {
        return match ($tab) {
            'organizacija' => $this->can($organizationId, $userId, 'settings.manage')
                || $this->can($organizationId, $userId, 'people.access'),
            'kadar', 'podaci' => $this->can($organizationId, $userId, 'people.access')
                || $this->can($organizationId, $userId, 'settings.manage'),
            'vrijeme', 'odobrenja' => $this->can($organizationId, $userId, 'time.access')
                || $this->can($organizationId, $userId, 'people.access')
                || $this->can($organizationId, $userId, 'payroll.export'),
            'pristup' => $this->can($organizationId, $userId, 'team.manage'),
            'pretplata' => $this->can($organizationId, $userId, 'settings.manage'),
            default => false,
        };
    }
}
