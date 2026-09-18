<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\OrganizationRbacService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationRbacServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationRbacService $rbac;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rbac = app(OrganizationRbacService::class);
    }

    public function test_owner_has_team_people_and_time_permissions(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Owner);

        $this->assertTrue($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'people.access'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'time.access'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'payroll.export'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'time.lock'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'inspection.export'));
    }

    public function test_hr_can_manage_people_but_not_settings(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Hr);

        $this->assertTrue($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'people.access'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'settings.manage'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'time.lock'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'inspection.export'));
    }

    public function test_manager_can_access_time_but_not_people_admin(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Manager);

        $this->assertFalse($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'people.access'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'time.access'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'payroll.export'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'time.lock'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'inspection.export'));
    }

    public function test_accountant_can_export_payroll_but_not_manage_people(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Accountant);

        $this->assertFalse($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'people.access'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'payroll.export'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'time.lock'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'inspection.export'));
    }

    public function test_employee_can_view_dashboard_only(): void
    {
        [$organizationId, $userId] = $this->seedMembership(OrganizationRole::Employee);

        $this->assertTrue($this->rbac->can($organizationId, $userId, 'dashboard.view'));
        $this->assertTrue($this->rbac->can($organizationId, $userId, 'requests.submit'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'requests.approve'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'team.manage'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'people.access'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'time.access'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'time.lock'));
        $this->assertFalse($this->rbac->can($organizationId, $userId, 'inspection.export'));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function seedMembership(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Test d.o.o.',
            'slug' => 'test-firma',
            'status' => 'active',
            'plan' => 'basic',
        ]);

        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$organization->id, $user->id];
    }
}
