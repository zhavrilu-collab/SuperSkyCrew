<?php

namespace Tests\Feature;

use App\Enums\ExceptionCode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\ReminderKind;
use App\Enums\TimeEntryStatus;
use App\Mail\ReminderMail;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\ReminderSend;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_digest_goes_to_hr_and_linked_worker_once_per_day(): void
    {
        Mail::fake();
        $this->travelTo('2026-09-19 10:00:00');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'podsjetnici-firma');
        $worker = User::factory()->create([
            'name' => 'Iva Istek',
            'email' => 'iva.istek@hr.test',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'role' => OrganizationRole::Employee,
        ]);
        Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'first_name' => 'Iva',
            'last_name' => 'Istek',
            'status' => PersonStatus::Employee,
            'medical_expires_at' => '2026-09-25',
        ]);

        $sent = app(ReminderService::class)->run($organization);
        $this->assertSame(2, $sent);

        Mail::assertSent(ReminderMail::class, 2);
        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && $mail->heading === 'Isteci dokumenata'
                && $mail->subjectLine === 'Isteci dokumenata · '.$mail->organization->name
                && collect($mail->rows)->contains(fn (array $row) => $row['label'] === 'Iva Istek');
        });
        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) {
            return $mail->hasTo('iva.istek@hr.test')
                && $mail->heading === 'Isteci dokumenata'
                && collect($mail->rows)->contains(fn (array $row) => $row['label'] === 'Liječnički pregled');
        });

        $again = app(ReminderService::class)->run($organization);
        $this->assertSame(0, $again);
        Mail::assertSent(ReminderMail::class, 2);
    }

    public function test_forgotten_clock_out_mails_worker_and_manager(): void
    {
        Mail::fake();
        $this->travelTo('2026-09-19 10:00:00');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'odjava-firma');
        $manager = User::factory()->create([
            'name' => 'Voditelj Odjava',
            'email' => 'voditelj.odjava@hr.test',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'role' => OrganizationRole::Manager,
        ]);
        $worker = User::factory()->create([
            'name' => 'Petar Odjava',
            'email' => 'petar.odjava@hr.test',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'role' => OrganizationRole::Employee,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'manager_user_id' => $manager->id,
            'first_name' => 'Petar',
            'last_name' => 'Odjava',
            'status' => PersonStatus::Employee,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => '2026-09-18',
            'started_at' => '2026-09-18 08:00:00',
            'status' => TimeEntryStatus::Complete,
            'exception_code' => ExceptionCode::MissingOut->value,
        ]);

        $sent = app(ReminderService::class)->run($organization);
        $this->assertSame(2, $sent);
        Mail::assertNotSent(ReminderMail::class, fn (ReminderMail $mail) => $mail->hasTo($owner->email));
        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) {
            return $mail->hasTo('petar.odjava@hr.test')
                && $mail->heading === 'Zaboravljena odjava'
                && str_contains($mail->intro, 'Niste se odjavili');
        });
        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) {
            return $mail->hasTo('voditelj.odjava@hr.test')
                && $mail->heading === 'Zaboravljena odjava'
                && str_contains($mail->intro, 'Petar Odjava');
        });
    }

    public function test_incomplete_slog_on_day_five_and_seven(): void
    {
        Mail::fake();
        $this->travelTo('2026-09-19 10:00:00');
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'slog-firma');
        $worker = User::factory()->create([
            'email' => 'ana.slog@hr.test',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'role' => OrganizationRole::Employee,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $worker->id,
            'first_name' => 'Ana',
            'last_name' => 'Slog',
            'status' => PersonStatus::Employee,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => '2026-09-14',
            'status' => TimeEntryStatus::Draft,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => '2026-09-12',
            'status' => TimeEntryStatus::Complete,
            'exception_code' => ExceptionCode::MissingOut->value,
        ]);
        TimeEntry::query()->create([
            'organization_id' => $organization->id,
            'person_id' => $person->id,
            'work_date' => '2026-09-13',
            'status' => TimeEntryStatus::Complete,
            'absence_code' => 'GO',
            'absence_minutes' => 480,
        ]);

        $sent = app(ReminderService::class)->run($organization);
        $this->assertSame(4, $sent);

        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && $mail->heading === 'Nekompletni slogovi'
                && str_contains($mail->intro, 'još 2 dana')
                && collect($mail->rows)->contains(fn (array $row) => $row['label'] === 'Ana Slog');
        });
        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && $mail->heading === 'Nekompletni slogovi'
                && str_contains($mail->intro, '7. dan');
        });
        Mail::assertSent(ReminderMail::class, function (ReminderMail $mail) {
            return $mail->hasTo('ana.slog@hr.test') && $mail->heading === 'Nekompletan slog';
        });

        $this->assertSame(4, ReminderSend::query()->where('kind', ReminderKind::Incomplete->value)->count());
    }

    public function test_command_sends_for_active_tenants_only(): void
    {
        Mail::fake();
        $this->travelTo('2026-09-19 10:00:00');
        [, $active] = $this->seedMember(OrganizationRole::Owner, 'aktivni-podsjetnik');
        Person::factory()->create([
            'organization_id' => $active->id,
            'status' => PersonStatus::Employee,
            'work_permit_expires_at' => '2026-09-20',
        ]);
        $paused = Organization::query()->create([
            'name' => 'Pauza d.o.o.',
            'slug' => 'pauza-podsjetnik',
            'status' => OrganizationStatus::Suspended,
            'plan' => 'standard',
        ]);
        Person::factory()->create([
            'organization_id' => $paused->id,
            'status' => PersonStatus::Employee,
            'work_permit_expires_at' => '2026-09-20',
        ]);

        $this->artisan('hr:reminders')
            ->expectsOutput('Poslano podsjetnika: 1')
            ->assertSuccessful();

        Mail::assertSent(ReminderMail::class, 1);
    }

    public function test_owner_sees_notifications_settings(): void
    {
        [$owner, $organization] = $this->seedMember(OrganizationRole::Owner, 'obavijesti-firma');

        $this->actingAs($owner)
            ->get(route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'vrijeme',
                'section' => 'obavijesti',
            ]))
            ->assertOk()
            ->assertSee('Obavijesti')
            ->assertSee('hr:reminders')
            ->assertSee('hr:close-time')
            ->assertSee('Zaboravljena odjava')
            ->assertSee('Nekompletan slog')
            ->assertSee('Broj poslanih poruka danas: 0')
            ->assertSee('text-tema', false);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedMember(OrganizationRole $role, string $slug): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Podsjetnici d.o.o.',
            'slug' => $slug,
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $organization];
    }
}
