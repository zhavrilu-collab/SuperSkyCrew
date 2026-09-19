<?php

namespace App\Services;

use App\Enums\ExceptionCode;
use App\Enums\ExpiryKind;
use App\Enums\OrganizationStatus;
use App\Enums\ReminderKind;
use App\Enums\TimeEntryStatus;
use App\Mail\ReminderMail;
use App\Models\Organization;
use App\Models\Person;
use App\Models\ReminderSend;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    public const INCOMPLETE_DAYS = [5, 7];

    public const MISSING_OUT_LOOKBACK_DAYS = 3;

    public function __construct(
        private readonly ExpiryWarningService $expiries,
        private readonly OrganizationRbacService $rbac,
    ) {}

    public function runAll(): int
    {
        $sent = 0;
        Organization::query()
            ->where('status', OrganizationStatus::Active)
            ->orderBy('id')
            ->each(function (Organization $organization) use (&$sent) {
                $sent += $this->run($organization);
            });

        return $sent;
    }

    public function run(Organization $organization): int
    {
        return $this->sendExpiries($organization)
            + $this->sendMissingOuts($organization)
            + $this->sendIncomplete($organization);
    }

    private function sendExpiries(Organization $organization): int
    {
        $items = $this->expiries->due($organization);
        if ($items === []) {
            return 0;
        }

        $today = $this->today()->toDateString();
        $sent = 0;

        $hrRows = array_map(fn (array $item) => [
            'label' => $item['person']->fullName(),
            'value' => $this->expiryValue($item),
        ], $items);

        foreach ($this->usersWith($organization, 'people.access') as $user) {
            if ($this->mailOnce($organization, $user, ReminderKind::Expiry, 'digest:'.$today, new ReminderMail(
                organization: $organization,
                greetingName: $user->name,
                heading: 'Isteci dokumenata',
                intro: 'U narednih '.$this->expiries->horizon($organization).' dana (ili već) ističu dokumenti i rokovi kadra.',
                rows: $hrRows,
                actionLabel: 'Otvori isteke',
                actionUrl: url('/'.$organization->slug.'/isteci'),
                footer: 'Jedan sažetak dnevno. Prag upozorenja postavljate u Postavke → Kadrovi → Isteci.',
                subjectLine: 'Isteci dokumenata · '.$organization->name,
            ))) {
                $sent++;
            }
        }

        $byPerson = collect($items)->groupBy(fn (array $item) => $item['person']->id);
        foreach ($byPerson as $personItems) {
            /** @var Person $person */
            $person = $personItems->first()['person'];
            $user = $person->user;
            if ($user === null || $user->email === null) {
                continue;
            }
            if ($this->rbac->can($organization->id, $user->id, 'people.access')) {
                continue;
            }

            $rows = $personItems->map(fn (array $item) => [
                'label' => $item['kind']->label(),
                'value' => $this->expiryValue($item, false),
            ])->values()->all();

            if ($this->mailOnce($organization, $user, ReminderKind::Expiry, 'person:'.$person->id.':'.$today, new ReminderMail(
                organization: $organization,
                greetingName: $person->fullName(),
                heading: 'Isteci dokumenata',
                intro: 'Imate dokument ili rok koji ističe. Javite HR-u ako treba obnoviti.',
                rows: $rows,
                actionLabel: 'Moj tjedan',
                actionUrl: url('/'.$organization->slug.'/moj-tjedan'),
                footer: 'Poruka je automatski podsjetnik, jednom dnevno dok je rok unutar pragova.',
                subjectLine: 'Istek dokumenta · '.$organization->name,
            ))) {
                $sent++;
            }
        }

        return $sent;
    }

    private function sendMissingOuts(Organization $organization): int
    {
        $today = $this->today();
        $from = $today->copy()->subDays(self::MISSING_OUT_LOOKBACK_DAYS)->toDateString();
        $before = $today->toDateString();

        $entries = TimeEntry::query()
            ->forOrganization($organization)
            ->with(['person.user', 'person.manager'])
            ->where('exception_code', ExceptionCode::MissingOut->value)
            ->whereNull('exception_resolved_at')
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<', $before)
            ->orderBy('work_date')
            ->get();

        $sent = 0;
        foreach ($entries as $entry) {
            $person = $entry->person;
            if ($person === null || ! $person->status->clocksIn()) {
                continue;
            }

            $workDate = $entry->work_date?->format('d.m.Y.') ?? '—';
            $rows = [
                ['label' => 'Dan', 'value' => $workDate],
                ['label' => 'Prijava', 'value' => $entry->started_at?->timezone(config('app.timezone'))->format('H:i') ?? '—'],
                ['label' => 'Odjava', 'value' => 'nema'],
            ];

            $recipients = $this->workerAndManager($person);
            foreach ($recipients as $user) {
                $isWorker = $person->user_id === $user->id;
                if ($this->mailOnce($organization, $user, ReminderKind::MissingOut, 'entry:'.$entry->id, new ReminderMail(
                    organization: $organization,
                    greetingName: $isWorker ? $person->fullName() : $user->name,
                    heading: 'Zaboravljena odjava',
                    intro: $isWorker
                        ? 'Niste se odjavili. Dopunite slog ili javite voditelju da unese korekciju.'
                        : $person->fullName().' nije se odjavio/la. Provjerite iznimku ili zatražite korekciju.',
                    rows: $isWorker ? $rows : array_merge(
                        [['label' => 'Osoba', 'value' => $person->fullName()]],
                        $rows
                    ),
                    actionLabel: $isWorker ? 'Prijava / odjava' : 'Iznimke',
                    actionUrl: $isWorker
                        ? url('/'.$organization->slug.'/prijava')
                        : url('/'.$organization->slug.'/iznimke'),
                    footer: 'Jednom po danu dok se odjava ne upiše ili iznimka ne riješi.',
                    subjectLine: 'Zaboravljena odjava · '.$organization->name,
                ))) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    private function sendIncomplete(Organization $organization): int
    {
        $today = $this->today();
        $sent = 0;

        foreach (self::INCOMPLETE_DAYS as $offset) {
            $workDate = $today->copy()->subDays($offset)->toDateString();
            $entries = TimeEntry::query()
                ->forOrganization($organization)
                ->with(['person.user', 'person.manager'])
                ->whereDate('work_date', $workDate)
                ->where('status', '!=', TimeEntryStatus::Locked->value)
                ->whereNull('absence_code')
                ->where(function ($query) {
                    $query->where('status', TimeEntryStatus::Draft->value)
                        ->orWhere(function ($inner) {
                            $inner->whereNotNull('exception_code')
                                ->whereNull('exception_resolved_at');
                        });
                })
                ->orderBy('person_id')
                ->get()
                ->filter(fn (TimeEntry $entry) => $entry->person !== null && $entry->person->status->clocksIn());

            if ($entries->isEmpty()) {
                continue;
            }

            $digestRows = $entries->map(fn (TimeEntry $entry) => [
                'label' => $entry->person->fullName(),
                'value' => $this->incompleteValue($entry, $offset),
            ])->values()->all();

            $deadlineNote = $offset === 7
                ? 'Danas je 7. dan: unos evidencije prema čl. 13. Pravilnika (NN 55/2024) mora biti gotov.'
                : 'Do unosa evidencije ostala su još 2 dana (rok je 7. dan, čl. 13.).';

            foreach ($this->usersWith($organization, 'people.access') as $user) {
                if ($this->mailOnce($organization, $user, ReminderKind::Incomplete, 'digest:'.$workDate.':'.$offset, new ReminderMail(
                    organization: $organization,
                    greetingName: $user->name,
                    heading: 'Nekompletni slogovi',
                    intro: $deadlineNote,
                    rows: $digestRows,
                    actionLabel: 'Šihterica',
                    actionUrl: url('/'.$organization->slug.'/sihterica'),
                    footer: 'Podsjetnik ide 5. i 7. dana nakon datuma rada. Ponavlja se samo ako slog i dalje nije zatvoren, jednom po roku.',
                    subjectLine: 'Nekompletni slogovi ('.$offset.'. dan) · '.$organization->name,
                ))) {
                    $sent++;
                }
            }

            foreach ($entries as $entry) {
                $person = $entry->person;
                $user = $person?->user;
                if ($user === null || $user->email === null) {
                    continue;
                }
                if ($this->rbac->can($organization->id, $user->id, 'people.access')) {
                    continue;
                }

                if ($this->mailOnce($organization, $user, ReminderKind::Incomplete, 'entry:'.$entry->id.':'.$offset, new ReminderMail(
                    organization: $organization,
                    greetingName: $person->fullName(),
                    heading: 'Nekompletan slog',
                    intro: $deadlineNote,
                    rows: [
                        ['label' => 'Dan', 'value' => $entry->work_date?->format('d.m.Y.') ?? '—'],
                        ['label' => 'Status', 'value' => $this->incompleteValue($entry, $offset)],
                    ],
                    actionLabel: 'Moj tjedan',
                    actionUrl: url('/'.$organization->slug.'/moj-tjedan'),
                    footer: 'Javite voditelju ili HR-u ako ne možete sami dopuniti slog.',
                    subjectLine: 'Nekompletan slog · '.$organization->name,
                ))) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @param  array{person: Person, kind: ExpiryKind, detail: string|null, date: Carbon, days: int, overdue: bool, window: int}  $item
     */
    private function expiryValue(array $item, bool $withKind = true): string
    {
        $when = $item['overdue']
            ? 'isteklo '.$item['date']->format('d.m.Y.')
            : 'ističe '.$item['date']->format('d.m.Y.').' ('.$item['days'].' d.)';
        $kind = $item['kind']->label();
        $detail = $item['detail'] ? ' · '.$item['detail'] : '';

        return $withKind ? $kind.$detail.' — '.$when : $when.$detail;
    }

    private function incompleteValue(TimeEntry $entry, int $offset): string
    {
        $parts = [$offset.'. dan'];
        if ($entry->hasOpenException()) {
            $parts[] = $entry->exceptionLabel();
        } else {
            $parts[] = $entry->status?->label() ?? 'U tijeku';
        }

        return implode(' · ', $parts);
    }

    /**
     * @return Collection<int, User>
     */
    private function usersWith(Organization $organization, string $permission): Collection
    {
        return $organization->members()
            ->with('user')
            ->get()
            ->map(fn ($membership) => $membership->user)
            ->filter(fn (?User $user) => $user !== null && filled($user->email) && $this->rbac->can($organization->id, $user->id, $permission))
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function workerAndManager(Person $person): Collection
    {
        return collect([$person->user, $person->manager])
            ->filter(fn (?User $user) => $user !== null && filled($user->email))
            ->unique('id')
            ->values();
    }

    private function mailOnce(Organization $organization, User $user, ReminderKind $kind, string $subjectKey, ReminderMail $mail): bool
    {
        $exists = ReminderSend::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('kind', $kind->value)
            ->where('subject_key', $subjectKey)
            ->exists();

        if ($exists) {
            return false;
        }

        Mail::to($user)->send($mail);

        ReminderSend::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'kind' => $kind->value,
            'subject_key' => $subjectKey,
            'sent_at' => now(),
        ]);

        return true;
    }

    private function today(): Carbon
    {
        return now()->timezone(config('app.timezone'))->startOfDay();
    }
}
