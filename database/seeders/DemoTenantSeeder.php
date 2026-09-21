<?php

namespace Database\Seeders;

use App\Enums\CalendarLevel;
use App\Enums\ClockChannel;
use App\Enums\ContractType;
use App\Enums\GeofenceMode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\OtherFoKind;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Enums\RequestType;
use App\Jobs\NotifyAdminConsoleJob;
use App\Models\CalendarRule;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\GrantEntry;
use App\Models\GrantProject;
use App\Models\JobPosition;
use App\Models\LegalEntity;
use App\Models\Location;
use App\Models\OpenShift;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\Punch;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkCenter;
use App\Models\WorkflowRequest;
use App\Services\ClockService;
use App\Services\CoreAuthService;
use App\Services\EmploymentContractService;
use App\Services\HrSetupService;
use App\Services\LeaveService;
use App\Services\OrganizationStructureService;
use App\Services\PersonEngagementService;
use App\Services\WorkflowEngine;
use App\Support\CroatianOib;
use App\Support\OrganizationFeatures;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemoTenantSeeder extends Seeder
{
    public const SLUG = 'demo-hr';

    public const PASSWORD = 'DemoHr.2026';

    public const MAIL_DOMAIN = 'hr.demo';

    /**
     * @return list<array{role: OrganizationRole, email: string, name: string}>
     */
    public static function accounts(): array
    {
        return [
            ['role' => OrganizationRole::Owner, 'email' => 'vlasnik@'.self::MAIL_DOMAIN, 'name' => 'Marta Vlasnik'],
            ['role' => OrganizationRole::Hr, 'email' => 'hr@'.self::MAIL_DOMAIN, 'name' => 'HR Administrator'],
            ['role' => OrganizationRole::Manager, 'email' => 'voditelj@'.self::MAIL_DOMAIN, 'name' => 'Petra Voditelj'],
            ['role' => OrganizationRole::Accountant, 'email' => 'knjigovo@'.self::MAIL_DOMAIN, 'name' => 'Lana Knjigovođa'],
            ['role' => OrganizationRole::Employee, 'email' => 'radnik@'.self::MAIL_DOMAIN, 'name' => 'Ivan Horvat'],
        ];
    }

    public function run(): void
    {
        $this->call(NkzOccupationSeeder::class);

        Notification::fake();

        $users = [];
        foreach (self::accounts() as $account) {
            $users[$account['role']->value] = $this->user($account['email'], $account['name']);
        }

        $features = [];
        foreach (OrganizationFeatures::KEYS as $key) {
            $features[$key] = true;
        }

        $organization = Organization::query()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Demo HR d.o.o.',
                'status' => OrganizationStatus::Active,
                'plan' => 'premium',
                'employee_limit' => 200,
                'features' => $features,
                'email' => 'info@'.self::MAIL_DOMAIN,
                'oib' => $this->oib('8182636454'),
                'phone' => '+385 1 555 0100',
                'city' => 'Zagreb',
                'organization_type' => OrganizationType::Company,
                'theme_key' => 'tirkizna',
                'volunteer_module' => true,
                'annual_leave_base_days' => 20,
                'annual_leave_days_per_child' => 2,
                'show_clock_bounds' => true,
            ],
        );

        foreach (self::accounts() as $account) {
            OrganizationUser::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $users[$account['role']->value]->id,
                ],
                ['role' => $account['role']],
            );
        }

        $location = Location::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Sjedište Zagreb'],
            [
                'latitude' => 45.8150,
                'longitude' => 15.9819,
                'radius_meters' => 250,
                'geofence_mode' => GeofenceMode::Off,
                'is_active' => true,
                'kiosk_enabled' => true,
                'kiosk_token' => 'kiosk-demo-hr',
                'allow_offline' => true,
            ],
        );

        $rootUnit = app(OrganizationStructureService::class)->ensure($organization);
        $legal = LegalEntity::query()->forOrganization($organization)->orderBy('id')->first();
        $zagreb = WorkCenter::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'ZG'],
            [
                'name' => 'Zagreb',
                'legal_entity_id' => $legal?->id,
                'location_id' => $location->id,
                'city' => 'Zagreb',
                'street' => 'Ilica 1',
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );
        $rootUnit->update([
            'legal_entity_id' => $legal?->id,
            'work_center_id' => $zagreb->id,
            'name' => $organization->name,
        ]);

        $uprava = Department::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'UPR'],
            [
                'name' => 'Uprava',
                'manager_user_id' => $users['owner']->id,
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );
        $operativa = Department::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'OPS'],
            [
                'name' => 'Operativa',
                'parent_id' => $uprava->id,
                'manager_user_id' => $users['manager']->id,
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );

        $direktorica = JobPosition::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Direktorica'],
            [
                'department_id' => $uprava->id,
                'rad1g' => '1120',
                'annual_leave_days' => 25,
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );
        $referent = JobPosition::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Referent'],
            [
                'department_id' => $operativa->id,
                'rad1g' => '4110',
                'annual_leave_days' => 20,
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );

        $mtUprava = CostCenter::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => '100'],
            [
                'name' => 'Uprava',
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );
        $mtOperativa = CostCenter::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => '200'],
            [
                'name' => 'Operativa',
                'valid_from' => now()->subYears(3)->toDateString(),
            ],
        );

        $ownerPerson = $this->person($organization, [
            'user_id' => $users['owner']->id,
            'first_name' => 'Marta',
            'last_name' => 'Vlasnik',
            'oib' => $this->oib('1000000001'),
            'job_title' => 'Direktorica',
            'status' => PersonStatus::Employee,
            'contract_type' => ContractType::Indefinite,
            'started_at' => now()->subYears(2)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $uprava->id,
            'job_position_id' => $direktorica->id,
            'cost_center_id' => $mtUprava->id,
            'annual_leave_days' => 25,
            'clock_pin' => '1111',
        ]);

        $this->person($organization, [
            'user_id' => $users['hr']->id,
            'first_name' => 'Ivana',
            'last_name' => 'Kadrovik',
            'oib' => $this->oib('1000000002'),
            'job_title' => 'HR administrator',
            'status' => PersonStatus::Employee,
            'contract_type' => ContractType::Indefinite,
            'started_at' => now()->subYear()->toDateString(),
            'location_id' => $location->id,
            'department_id' => $uprava->id,
            'cost_center_id' => $mtUprava->id,
            'annual_leave_days' => 22,
            'clock_pin' => '3333',
        ]);

        $this->person($organization, [
            'user_id' => $users['manager']->id,
            'first_name' => 'Petra',
            'last_name' => 'Voditelj',
            'oib' => $this->oib('1000000003'),
            'job_title' => 'Voditeljica operative',
            'status' => PersonStatus::Employee,
            'contract_type' => ContractType::Indefinite,
            'started_at' => now()->subYears(4)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $operativa->id,
            'job_position_id' => $referent->id,
            'cost_center_id' => $mtOperativa->id,
            'manager_user_id' => $users['owner']->id,
            'annual_leave_days' => 22,
            'clock_pin' => '4444',
        ]);

        $workerPerson = $this->person($organization, [
            'user_id' => $users['employee']->id,
            'first_name' => 'Ivan',
            'last_name' => 'Horvat',
            'oib' => $this->oib('1000000004'),
            'job_title' => 'Referent',
            'status' => PersonStatus::Employee,
            'contract_type' => ContractType::Indefinite,
            'started_at' => now()->subMonths(8)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $operativa->id,
            'job_position_id' => $referent->id,
            'cost_center_id' => $mtOperativa->id,
            'manager_user_id' => $users['manager']->id,
            'annual_leave_days' => 20,
            'children_count' => 1,
            'iban' => 'HR1210010051863000160',
            'pay_coefficient' => 1.15,
            'clock_pin' => '2222',
            'medical_expires_at' => now()->addDays(12)->toDateString(),
        ]);

        $this->person($organization, [
            'first_name' => 'Ana',
            'last_name' => 'Kovač',
            'oib' => $this->oib('1000000005'),
            'job_title' => 'Referentica',
            'status' => PersonStatus::Employee,
            'contract_type' => ContractType::Indefinite,
            'started_at' => now()->subMonths(14)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $operativa->id,
            'job_position_id' => $referent->id,
            'cost_center_id' => $mtOperativa->id,
            'manager_user_id' => $users['manager']->id,
            'clock_pin' => '5555',
        ]);

        $this->person($organization, [
            'first_name' => 'Luka',
            'last_name' => 'Student',
            'status' => PersonStatus::OtherFo,
            'fo_kind' => OtherFoKind::Student,
            'instrument_title' => 'Ugovor o obavljanju studentskih poslova',
            'started_at' => now()->subMonths(2)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $operativa->id,
            'clock_pin' => '6666',
        ]);

        $this->person($organization, [
            'first_name' => 'Iva',
            'last_name' => 'Honor',
            'status' => PersonStatus::Contractor,
            'instrument_title' => 'Ugovor o djelu — prijevod',
            'started_at' => now()->subMonths(1)->toDateString(),
            'location_id' => $location->id,
        ]);

        $this->person($organization, [
            'first_name' => 'Ante',
            'last_name' => 'Ustup',
            'job_title' => 'Referent',
            'status' => PersonStatus::Assigned,
            'host_employer' => 'Agencija Plus d.o.o.',
            'assignment_clocks' => true,
            'started_at' => now()->subMonths(4)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $operativa->id,
            'clock_pin' => '7777',
        ]);

        $this->person($organization, [
            'first_name' => 'Roko',
            'last_name' => 'Ruk',
            'job_title' => 'Direktorica',
            'status' => PersonStatus::Executive,
            'executive_autonomy' => true,
            'started_at' => now()->subYears(5)->toDateString(),
            'location_id' => $location->id,
            'department_id' => $uprava->id,
            'job_position_id' => $direktorica->id,
            'clock_pin' => '8888',
        ]);

        $this->person($organization, [
            'first_name' => 'Sara',
            'last_name' => 'Kandidat',
            'status' => PersonStatus::Candidate,
            'job_title' => 'Referent',
            'started_at' => now()->toDateString(),
        ]);

        $this->person($organization, [
            'first_name' => 'Ema',
            'last_name' => 'Volonter',
            'status' => PersonStatus::Volunteer,
            'started_at' => now()->subMonths(3)->toDateString(),
            'location_id' => $location->id,
        ]);

        $shift = Shift::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'P1'],
            [
                'name' => 'Prva',
                'starts_at' => '08:00:00',
                'ends_at' => '16:00:00',
                'break_minutes' => 30,
                'is_night' => false,
                'is_shift' => true,
            ],
        );
        foreach ([1, 2, 3, 4, 5] as $weekday) {
            CalendarRule::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'level' => CalendarLevel::Organization,
                    'weekday' => $weekday,
                    'department_id' => null,
                    'job_position_id' => null,
                    'person_id' => null,
                ],
                [
                    'shift_id' => $shift->id,
                    'valid_from' => now()->subYears(3)->toDateString(),
                    'valid_to' => null,
                ],
            );
        }

        $contracts = app(EmploymentContractService::class);
        $engagements = app(PersonEngagementService::class);
        Person::query()->where('organization_id', $organization->id)->orderBy('id')->get()
            ->each(function (Person $person) use ($contracts, $engagements, $legal, $zagreb) {
                if (! $person->legal_entity_id || ! $person->work_center_id) {
                    $person->forceFill([
                        'legal_entity_id' => $person->legal_entity_id ?: $legal?->id,
                        'work_center_id' => $person->work_center_id ?: $zagreb->id,
                    ])->save();
                }
                $contracts->seedIfMissing($person);
                $engagements->sync($person);
            });

        app(HrSetupService::class)->provision($organization);
        app(LeaveService::class)->recalculateOrganization($organization);

        $project = GrantProject::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'code' => 'ESF'],
            ['name' => 'ESF vještine 2026', 'is_active' => true],
        );

        OpenShift::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'work_date' => Carbon::parse('2026-09-21')->toDateString(),
            ],
            [
                'shift_id' => $shift->id,
                'department_id' => $operativa->id,
                'slots' => 2,
                'note' => 'Pojačanje u ponedjeljak',
            ],
        );

        $this->seedTimesheet($organization, $workerPerson, $users['employee']);
        $this->seedGrantHours($organization, $workerPerson, $project);
        $this->seedRequests($organization, $workerPerson, $users['employee'], $users['manager']);

        if (! app()->runningUnitTests()) {
            NotifyAdminConsoleJob::dispatchSync($organization->id);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function person(Organization $organization, array $attributes): Person
    {
        $match = ['organization_id' => $organization->id];
        if (isset($attributes['user_id'])) {
            $match['user_id'] = $attributes['user_id'];
        } else {
            $match['first_name'] = $attributes['first_name'];
            $match['last_name'] = $attributes['last_name'];
        }

        return Person::query()->updateOrCreate($match, $attributes + [
            'organization_id' => $organization->id,
            'citizenship' => 'HR',
            'annual_leave_manual' => false,
        ]);
    }

    private function user(string $email, string $name): User
    {
        $existing = User::query()->where('email', $email)->first()
            ?? User::query()->where('email', $this->legacyEmail($email))->first();

        $coreUserId = $this->ensureCoreUser($name, $email) ?? $existing?->core_user_id;

        $payload = [
            'name' => $name,
            'email' => $email,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
            'core_user_id' => $coreUserId,
        ];

        if ($existing !== null) {
            $existing->forceFill($payload)->save();

            return $existing->fresh();
        }

        return User::query()->create($payload);
    }

    private function legacyEmail(string $email): string
    {
        return str_replace('@'.self::MAIL_DOMAIN, '@hr-demo.superskytech.com', $email);
    }

    private function ensureCoreUser(string $name, string $email): ?int
    {
        $core = app(CoreAuthService::class);
        if (! $core->isEnabled() || app()->runningUnitTests()) {
            return null;
        }

        try {
            return $core->registerWithCredentials($name, $email, self::PASSWORD, self::PASSWORD)['core_user_id'];
        } catch (ValidationException) {
            try {
                return $core->loginWithCredentials($email, self::PASSWORD)['core_user_id'];
            } catch (ValidationException) {
                return null;
            }
        }
    }

    private function seedTimesheet(Organization $organization, Person $worker, User $actor): void
    {
        if (Punch::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        $clock = app(ClockService::class);
        foreach (['2026-09-14', '2026-09-15', '2026-09-16', '2026-09-17', '2026-09-18'] as $day) {
            $clock->punch($worker, $actor, [
                'type' => PunchType::In->value,
                'occurred_at' => $day.' 08:00:00',
                'channel' => ClockChannel::Web->value,
                'client_event_id' => (string) Str::uuid(),
            ]);
            $clock->punch($worker, $actor, [
                'type' => PunchType::Out->value,
                'occurred_at' => $day.' 16:00:00',
                'channel' => ClockChannel::Web->value,
                'client_event_id' => (string) Str::uuid(),
            ]);
        }
    }

    private function seedGrantHours(Organization $organization, Person $worker, GrantProject $project): void
    {
        GrantEntry::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'person_id' => $worker->id,
                'grant_project_id' => $project->id,
                'work_date' => '2026-09-18',
            ],
            ['minutes' => 240, 'note' => 'Radionica za korisnike'],
        );
    }

    private function seedRequests(Organization $organization, Person $worker, User $workerUser, User $manager): void
    {
        if (WorkflowRequest::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        $engine = app(WorkflowEngine::class);
        $pending = $engine->submit($organization, $worker, $workerUser, RequestType::LeaveAnnual, [
            'from' => '2026-09-21',
            'to' => '2026-09-22',
            'note' => 'Obitelj — čeka voditelja',
        ]);
        $this->command?->info('Otvoren zahtjev #'.$pending->id.' (GO, čeka voditelja).');

        $approved = $engine->submit($organization, $worker, $workerUser, RequestType::LeaveAnnual, [
            'from' => '2026-09-07',
            'to' => '2026-09-08',
            'note' => 'Već odobreno',
        ]);
        $engine->approve($approved, $manager, 'Demo odobrenje');

        $engine->submit($organization, $worker, $workerUser, RequestType::Overtime, [
            'from' => '2026-09-18',
            'minutes' => 90,
            'note' => 'Zatvaranje naloga',
        ]);
    }

    private function oib(string $tenDigits): string
    {
        $a = 10;
        for ($i = 0; $i < 10; $i++) {
            $a = ($a + (int) $tenDigits[$i]) % 10;
            if ($a === 0) {
                $a = 10;
            }
            $a = ($a * 2) % 11;
        }

        $check = 11 - $a;
        if ($check === 10) {
            $check = 0;
        }

        $oib = $tenDigits.$check;
        if (! CroatianOib::isValid($oib)) {
            throw new \RuntimeException('Neispravan demo OIB: '.$oib);
        }

        return $oib;
    }
}
