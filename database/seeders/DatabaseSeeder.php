<?php

namespace Database\Seeders;

use App\Enums\ContractType;
use App\Enums\GeofenceMode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\OtherFoKind;
use App\Enums\PersonStatus;
use App\Models\CalendarRule;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmploymentContractService;
use App\Services\HrSetupService;
use App\Services\LeaveService;
use App\Services\PersonEngagementService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(NkzOccupationSeeder::class);

        $owner = User::query()->updateOrCreate(
            ['email' => 'vlasnik@hr.test'],
            [
                'name' => 'HR Vlasnik',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $hr = User::query()->updateOrCreate(
            ['email' => 'hr@hr.test'],
            [
                'name' => 'HR Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'voditelj@hr.test'],
            [
                'name' => 'Petra Voditelj',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $worker = User::query()->updateOrCreate(
            ['email' => 'radnik@hr.test'],
            [
                'name' => 'Ivan Horvat',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $accountant = User::query()->updateOrCreate(
            ['email' => 'knjigovo@hr.test'],
            [
                'name' => 'Lana Knjigovođa',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $organizations = [
            [
                'name' => 'Demo tvrtka Zagreb',
                'slug' => 'demo-tvrtka-zg',
                'status' => OrganizationStatus::Active,
                'plan' => 'standard',
                'email' => 'info@demo-tvrtka.hr',
                'oib' => '12345678903',
                'annual_leave_days_per_child' => 2,
                'theme_key' => 'tirkizna',
            ],
            [
                'name' => 'Demo udruga Split',
                'slug' => 'demo-udruga-st',
                'status' => OrganizationStatus::Pending,
                'plan' => 'basic',
                'email' => 'ured@demo-udruga.hr',
                'oib' => '10987654326',
                'theme_key' => 'tirkizna',
            ],
        ];

        foreach ($organizations as $organizationData) {
            Organization::query()->updateOrCreate(
                ['slug' => $organizationData['slug']],
                $organizationData,
            );
        }

        $activeOrg = Organization::query()->where('slug', 'demo-tvrtka-zg')->first();

        if ($activeOrg === null) {
            return;
        }

        OrganizationUser::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $owner->id],
            ['role' => OrganizationRole::Owner],
        );
        OrganizationUser::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $hr->id],
            ['role' => OrganizationRole::Hr],
        );
        OrganizationUser::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $manager->id],
            ['role' => OrganizationRole::Manager],
        );
        OrganizationUser::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $worker->id],
            ['role' => OrganizationRole::Employee],
        );
        OrganizationUser::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $accountant->id],
            ['role' => OrganizationRole::Accountant],
        );

        $location = Location::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'name' => 'Sjedište Zagreb'],
            [
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'radius_meters' => 250,
                'geofence_mode' => GeofenceMode::Off,
                'is_active' => true,
                'kiosk_enabled' => true,
                'kiosk_token' => 'kiosk-demo-zagreb',
            ],
        );

        $uprava = Department::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'code' => 'UPR'],
            [
                'name' => 'Uprava',
                'manager_user_id' => $owner->id,
                'valid_from' => now()->subYears(3)->toDateString(),
                'valid_to' => null,
            ],
        );
        $operativa = Department::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'code' => 'OPS'],
            [
                'name' => 'Operativa',
                'parent_id' => $uprava->id,
                'manager_user_id' => $manager->id,
                'valid_from' => now()->subYears(3)->toDateString(),
                'valid_to' => null,
            ],
        );

        $direktorica = JobPosition::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'name' => 'Direktorica'],
            [
                'department_id' => $uprava->id,
                'rad1g' => '1120',
                'annual_leave_days' => 25,
                'valid_from' => now()->subYears(3)->toDateString(),
                'valid_to' => null,
            ],
        );
        $referent = JobPosition::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'name' => 'Referent'],
            [
                'department_id' => $operativa->id,
                'rad1g' => '4110',
                'annual_leave_days' => 20,
                'valid_from' => now()->subYears(3)->toDateString(),
                'valid_to' => null,
            ],
        );

        $mtUprava = CostCenter::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'code' => '100'],
            [
                'name' => 'Uprava',
                'valid_from' => now()->subYears(3)->toDateString(),
                'valid_to' => null,
            ],
        );
        $mtOperativa = CostCenter::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'code' => '200'],
            [
                'name' => 'Operativa',
                'valid_from' => now()->subYears(3)->toDateString(),
                'valid_to' => null,
            ],
        );

        Person::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $owner->id],
            [
                'first_name' => 'Marta',
                'last_name' => 'Vlasnik',
                'job_title' => 'Direktorica',
                'status' => PersonStatus::Employee,
                'contract_type' => ContractType::Indefinite,
                'started_at' => now()->subYears(2)->toDateString(),
                'location_id' => $location->id,
                'department_id' => $uprava->id,
                'job_position_id' => $direktorica->id,
                'cost_center_id' => $mtUprava->id,
                'citizenship' => 'HR',
                'annual_leave_days' => 25,
                'annual_leave_manual' => false,
                'clock_pin' => '1111',
            ],
        );

        Person::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'user_id' => $worker->id],
            [
                'first_name' => 'Ivan',
                'last_name' => 'Horvat',
                'job_title' => 'Referent',
                'status' => PersonStatus::Employee,
                'contract_type' => ContractType::Indefinite,
                'started_at' => now()->subMonths(8)->toDateString(),
                'location_id' => $location->id,
                'department_id' => $operativa->id,
                'job_position_id' => $referent->id,
                'cost_center_id' => $mtOperativa->id,
                'citizenship' => 'HR',
                'manager_user_id' => $manager->id,
                'annual_leave_days' => 20,
                'annual_leave_manual' => false,
                'clock_pin' => '2222',
                'medical_expires_at' => now()->addDays(10)->toDateString(),
                'iban' => 'HR1210010051863000160',
                'pay_coefficient' => 1.15,
                'allowance_percent' => 10,
                'prior_service_months' => 36,
                'children_count' => 1,
                'dependents_count' => 1,
                'tax_relief_note' => '1 dijete',
                'znr_exam_required' => true,
            ],
        );

        $contracts = app(EmploymentContractService::class);
        foreach (Person::query()->where('organization_id', $activeOrg->id)->get() as $person) {
            $contracts->seedIfMissing($person);
        }

        Person::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'last_name' => 'Student', 'first_name' => 'Luka'],
            [
                'job_title' => 'Pomoćni referent',
                'status' => PersonStatus::OtherFo,
                'fo_kind' => OtherFoKind::Student,
                'instrument_title' => 'Ugovor o obavljanju studentskih poslova',
                'started_at' => now()->subMonths(2)->toDateString(),
                'location_id' => $location->id,
                'department_id' => $operativa->id,
                'citizenship' => 'HR',
                'clock_pin' => '4444',
            ],
        );
        Person::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'last_name' => 'Honor', 'first_name' => 'Iva'],
            [
                'job_title' => 'Prevodioc',
                'status' => PersonStatus::Contractor,
                'instrument_title' => 'Ugovor o djelu — prijevod',
                'started_at' => now()->subMonths(1)->toDateString(),
                'location_id' => $location->id,
                'citizenship' => 'HR',
            ],
        );
        Person::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'last_name' => 'Ustup', 'first_name' => 'Ante'],
            [
                'job_title' => 'Referent',
                'status' => PersonStatus::Assigned,
                'host_employer' => 'Agencija Plus d.o.o.',
                'assignment_clocks' => true,
                'started_at' => now()->subMonths(4)->toDateString(),
                'location_id' => $location->id,
                'department_id' => $operativa->id,
                'job_position_id' => $referent->id,
                'citizenship' => 'HR',
                'clock_pin' => '5555',
            ],
        );
        Person::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'last_name' => 'Ruk', 'first_name' => 'Roko'],
            [
                'job_title' => 'Direktorica',
                'status' => PersonStatus::Executive,
                'executive_autonomy' => true,
                'started_at' => now()->subYears(5)->toDateString(),
                'location_id' => $location->id,
                'department_id' => $uprava->id,
                'job_position_id' => $direktorica->id,
                'citizenship' => 'HR',
                'clock_pin' => '6666',
                'annual_leave_manual' => false,
            ],
        );

        $engagements = app(PersonEngagementService::class);
        Person::query()->where('organization_id', $activeOrg->id)->orderBy('id')->get()
            ->each(fn (Person $person) => $engagements->sync($person));

        app(HrSetupService::class)->provision($activeOrg);
        app(LeaveService::class)->recalculateOrganization($activeOrg);

        $prva = Shift::query()->updateOrCreate(
            ['organization_id' => $activeOrg->id, 'code' => 'P1'],
            [
                'name' => 'Prva',
                'starts_at' => '08:00:00',
                'ends_at' => '16:00:00',
                'break_minutes' => 30,
                'is_night' => false,
            ],
        );
        foreach ([1, 2, 3, 4, 5] as $weekday) {
            CalendarRule::query()->updateOrCreate(
                [
                    'organization_id' => $activeOrg->id,
                    'level' => 'organization',
                    'weekday' => $weekday,
                    'department_id' => null,
                    'job_position_id' => null,
                    'person_id' => null,
                ],
                [
                    'shift_id' => $prva->id,
                    'valid_from' => now()->subYears(3)->toDateString(),
                    'valid_to' => null,
                ],
            );
        }
    }
}
