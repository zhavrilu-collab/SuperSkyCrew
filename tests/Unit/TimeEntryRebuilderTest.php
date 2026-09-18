<?php

namespace Tests\Unit;

use App\Enums\ClockChannel;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\PersonStatus;
use App\Enums\PunchType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\User;
use App\Services\ClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryRebuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_break_is_subtracted_from_total_minutes(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 08:00:00'));
        $clock->punch($person, $user, $this->punch(PunchType::BreakStart, '2026-09-18 12:00:00'));
        $clock->punch($person, $user, $this->punch(PunchType::BreakEnd, '2026-09-18 12:30:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 16:30:00'));

        $this->assertSame(30, $result['entry']->break_minutes);
        $this->assertSame(480, $result['entry']->total_minutes);
        $this->assertSame(0, $result['entry']->overtime_minutes);
        $this->assertSame('complete', $result['entry']->status->value);
    }

    public function test_night_minutes_cover_hours_between_22_and_06(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 21:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 23:00:00'));

        $this->assertSame(120, $result['entry']->total_minutes);
        $this->assertSame(60, $result['entry']->night_minutes);
    }

    public function test_overtime_starts_after_eight_hours(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 08:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 17:00:00'));

        $this->assertSame(540, $result['entry']->total_minutes);
        $this->assertSame(60, $result['entry']->overtime_minutes);
    }

    public function test_sunday_minutes_are_recorded(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-20 08:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-20 12:00:00'));

        $this->assertSame(240, $result['entry']->sunday_minutes);
        $this->assertSame(240, $result['entry']->total_minutes);
    }

    public function test_holiday_minutes_are_recorded_on_first_of_may(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-05-01 08:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-05-01 16:00:00'));

        $this->assertSame(480, $result['entry']->holiday_minutes);
        $this->assertSame(480, $result['entry']->evidential_minutes);
    }

    public function test_corrected_punch_replaces_original_in_rebuild(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 08:00:00'));
        $out = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 16:00:00'));

        $clock->correct(
            $person,
            $user,
            $out['punch'],
            \Carbon\Carbon::parse('2026-09-18 17:00:00'),
            'Kasna odjava',
        );

        $entry = \App\Models\TimeEntry::query()->where('person_id', $person->id)->first();
        $this->assertSame(540, $entry->total_minutes);
        $this->assertSame(60, $entry->overtime_minutes);
        $this->assertSame(PunchType::Out, $clock->currentState($person));
    }

    public function test_daily_rest_under_twelve_hours_is_flagged(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-17 08:00:00'));
        $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-17 22:00:00'));
        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 07:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 15:00:00'));

        $this->assertSame(\App\Enums\ExceptionCode::DailyRest->value, $result['entry']->exception_code);
    }

    public function test_twelve_hour_daily_rest_is_not_flagged(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-17 08:00:00'));
        $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-17 19:00:00'));
        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 07:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 15:00:00'));

        $this->assertNull($result['entry']->exception_code);
    }

    public function test_monthly_fund_excess_is_flagged(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $priorDays = \App\Support\WorkingDays::dates(
            \Carbon\Carbon::parse('2026-09-01'),
            \Carbon\Carbon::parse('2026-09-17'),
        );
        foreach ($priorDays as $date) {
            \App\Models\TimeEntry::query()->create([
                'organization_id' => $person->organization_id,
                'person_id' => $person->id,
                'work_date' => $date,
                'total_minutes' => 480,
                'evidential_minutes' => 480,
                'status' => \App\Enums\TimeEntryStatus::Complete,
            ]);
        }

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 08:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 17:00:00'));

        $this->assertSame(\App\Enums\ExceptionCode::MonthlyFund->value, $result['entry']->exception_code);
        $this->assertSame(540, $result['entry']->total_minutes);
        $this->assertSame(540, $result['entry']->evidential_minutes);
        $this->assertSame('RD', $result['entry']->evidential_code);
        $this->assertFalse($result['entry']->evidential_manual);
    }

    public function test_manual_evidential_hours_survive_rebuild(): void
    {
        [$user, $person] = $this->seedPerson();
        $clock = app(ClockService::class);

        $clock->punch($person, $user, $this->punch(PunchType::In, '2026-09-18 08:00:00'));
        $result = $clock->punch($person, $user, $this->punch(PunchType::Out, '2026-09-18 16:00:00'));
        $entry = $result['entry'];
        $entry->evidential_minutes = 450;
        $entry->evidential_code = 'RD';
        $entry->evidential_note = 'Zaokruživanje';
        $entry->evidential_manual = true;
        $entry->save();

        $rebuilt = app(\App\Services\TimeEntryRebuilder::class)->rebuild(
            $person,
            \Carbon\Carbon::parse('2026-09-18'),
        );

        $this->assertSame(480, $rebuilt->total_minutes);
        $this->assertSame(450, $rebuilt->evidential_minutes);
        $this->assertSame('RD', $rebuilt->evidential_code);
        $this->assertTrue($rebuilt->evidential_manual);
        $this->assertSame('Zaokruživanje', $rebuilt->evidential_note);
    }

    /**
     * @return array{0: User, 1: Person}
     */
    private function seedPerson(): array
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'Sati d.o.o.',
            'slug' => 'sati-firma-'.uniqid(),
            'status' => OrganizationStatus::Active,
            'plan' => 'standard',
        ]);
        OrganizationUser::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner,
        ]);
        $person = Person::factory()->create([
            'organization_id' => $organization->id,
            'status' => PersonStatus::Employee,
        ]);

        return [$user, $person];
    }

    /**
     * @return array{type: string, occurred_at: string, channel: string, reason: string}
     */
    private function punch(PunchType $type, string $occurredAt): array
    {
        return [
            'type' => $type->value,
            'occurred_at' => $occurredAt,
            'channel' => ClockChannel::Manager->value,
            'reason' => 'Test',
        ];
    }
}
