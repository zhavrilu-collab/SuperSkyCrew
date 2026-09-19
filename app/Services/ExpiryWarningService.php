<?php

namespace App\Services;

use App\Enums\ContractType;
use App\Enums\ExpiryKind;
use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\Person;
use Carbon\Carbon;

class ExpiryWarningService
{
    public const WITHIN_DAYS = 30;

    public function horizon(Organization $organization): int
    {
        return max(1, (int) ($organization->expiry_warning_days ?: self::WITHIN_DAYS));
    }

    /**
     * @return list<array{person: Person, kind: ExpiryKind, detail: string|null, date: Carbon, days: int, overdue: bool, window: int}>
     */
    public function due(Organization $organization, ?int $withinDays = null): array
    {
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $horizonDays = $withinDays ?? $this->horizon($organization);
        $horizon = $today->copy()->addDays($horizonDays);
        $items = [];

        $people = Person::query()
            ->forOrganization($organization)
            ->with(['qualifications', 'employmentContracts', 'documents.documentType'])
            ->whereNotIn('status', [PersonStatus::Former->value, PersonStatus::Candidate->value])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        foreach ($people as $person) {
            foreach ($this->datesFor($person) as [$kind, $date]) {
                $item = $this->item($person, $kind, $date, $today, $horizon, null);
                if ($item !== null) {
                    $items[] = $item;
                }
            }

            foreach ($person->qualifications as $qualification) {
                $item = $this->item(
                    $person,
                    ExpiryKind::Qualification,
                    $qualification->expires_at?->copy()->startOfDay(),
                    $today,
                    $horizon,
                    $qualification->title,
                );
                if ($item !== null) {
                    $items[] = $item;
                }
            }

            foreach ($person->documents as $document) {
                if ($document->isDisposed()) {
                    continue;
                }
                $item = $this->item(
                    $person,
                    ExpiryKind::Dossier,
                    $document->expires_on?->copy()->startOfDay(),
                    $today,
                    $horizon,
                    $document->label(),
                );
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }

        usort($items, function (array $a, array $b) {
            return [$a['days'], $a['person']->last_name] <=> [$b['days'], $b['person']->last_name];
        });

        return $items;
    }

    /**
     * @return array{person: Person, kind: ExpiryKind, detail: string|null, date: Carbon, days: int, overdue: bool, window: int}|null
     */
    private function item(Person $person, ExpiryKind $kind, ?Carbon $date, Carbon $today, Carbon $horizon, ?string $detail): ?array
    {
        if ($date === null || $date->gt($horizon)) {
            return null;
        }

        $days = (int) round($today->diffInDays($date, false));

        return [
            'person' => $person,
            'kind' => $kind,
            'detail' => $detail,
            'date' => $date,
            'days' => $days,
            'overdue' => $days < 0,
            'window' => $this->window($days),
        ];
    }

    /**
     * @param  list<array{person: Person, kind: ExpiryKind, date: Carbon, days: int, overdue: bool, window: int}>  $items
     * @return array<int, int>
     */
    public function countsByPerson(array $items): array
    {
        $counts = [];
        foreach ($items as $item) {
            $id = $item['person']->id;
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return list<array{0: ExpiryKind, 1: Carbon|null}>
     */
    private function datesFor(Person $person): array
    {
        $current = $person->currentContract();
        $contractEnd = $current?->ends_at?->copy()->startOfDay();
        if ($contractEnd === null && $person->contract_type === ContractType::FixedTerm && $person->ended_at) {
            $contractEnd = $person->ended_at->copy()->startOfDay();
        }

        return [
            [ExpiryKind::WorkPermit, $person->work_permit_expires_at?->copy()->startOfDay()],
            [ExpiryKind::Medical, $person->medical_expires_at?->copy()->startOfDay()],
            [ExpiryKind::Certificate, $person->certificate_expires_at?->copy()->startOfDay()],
            [ExpiryKind::FixedTerm, $contractEnd],
            [ExpiryKind::Trial, $current?->trial_ends_at?->copy()->startOfDay()],
        ];
    }

    private function window(int $days): int
    {
        if ($days < 0) {
            return 0;
        }
        if ($days <= 5) {
            return 5;
        }
        if ($days <= 10) {
            return 10;
        }
        if ($days <= 20) {
            return 20;
        }

        return 30;
    }
}
