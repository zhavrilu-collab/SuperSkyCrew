<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\User;
use App\Models\WorkflowRequest;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTenantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_tenant_has_all_roles_and_sample_hr_data(): void
    {
        $this->seed(DemoTenantSeeder::class);

        $organization = Organization::query()->where('slug', DemoTenantSeeder::SLUG)->first();
        $this->assertNotNull($organization);
        $this->assertSame(OrganizationStatus::Active, $organization->status);
        $this->assertSame('premium', $organization->plan);

        $this->assertSame(5, OrganizationUser::query()->where('organization_id', $organization->id)->count());
        foreach (OrganizationRole::cases() as $role) {
            $this->assertDatabaseHas('organization_users', [
                'organization_id' => $organization->id,
                'role' => $role->value,
            ]);
        }

        $worker = User::query()->where('email', 'radnik@hr-demo.superskytech.com')->first();
        $this->assertNotNull($worker);
        $this->assertTrue(password_verify(DemoTenantSeeder::PASSWORD, $worker->password));

        $this->assertGreaterThanOrEqual(8, Person::query()->where('organization_id', $organization->id)->count());
        $this->assertDatabaseHas('people', [
            'organization_id' => $organization->id,
            'status' => PersonStatus::Candidate->value,
            'last_name' => 'Kandidat',
        ]);

        $this->assertSame(10, Punch::query()->where('organization_id', $organization->id)->count());
        $this->assertDatabaseHas('workflow_requests', [
            'organization_id' => $organization->id,
            'type' => RequestType::LeaveAnnual->value,
            'status' => RequestStatus::Pending->value,
            'current_role' => OrganizationRole::Manager->value,
        ]);
        $this->assertTrue(
            WorkflowRequest::query()
                ->where('organization_id', $organization->id)
                ->where('status', RequestStatus::Approved->value)
                ->exists(),
        );

        $this->actingAs($worker)
            ->get(route('organization.dashboard', $organization->slug))
            ->assertOk();
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DemoTenantSeeder::class);
        $this->seed(DemoTenantSeeder::class);

        $this->assertSame(1, Organization::query()->where('slug', DemoTenantSeeder::SLUG)->count());
        $this->assertSame(5, User::query()->whereIn('email', collect(DemoTenantSeeder::accounts())->pluck('email'))->count());
        $this->assertSame(10, Punch::query()->count());
    }
}
