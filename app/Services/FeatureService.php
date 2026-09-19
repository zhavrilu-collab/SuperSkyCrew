<?php

namespace App\Services;

use App\Enums\PersonStatus;
use App\Models\Organization;
use App\Models\Person;
use App\Support\OrganizationFeatures;
use Illuminate\Validation\ValidationException;

class FeatureService
{
    public function enabled(Organization $organization, string $key): bool
    {
        if ($key === OrganizationFeatures::VOLUNTEER_MODULE && $organization->volunteer_module) {
            return true;
        }

        $resolved = $this->resolved($organization);

        return (bool) ($resolved[$key] ?? false);
    }

    public function assertEnabled(Organization $organization, string $key, string $message = 'Ova značajka nije uključena u paketu.'): void
    {
        if (! $this->enabled($organization, $key)) {
            abort(403, $message);
        }
    }

    public function employeeLimit(Organization $organization): ?int
    {
        if ($organization->employee_limit === null) {
            return null;
        }

        $limit = (int) $organization->employee_limit;

        return $limit > 0 ? $limit : null;
    }

    public function suggestedLimit(Organization $organization): ?int
    {
        return $this->employeeLimit($organization)
            ?? OrganizationFeatures::defaultLimit($organization->plan);
    }

    public function countedHeadcount(Organization $organization): int
    {
        return Person::query()
            ->forOrganization($organization)
            ->whereIn('status', [
                PersonStatus::Employee->value,
                PersonStatus::Assigned->value,
                PersonStatus::OtherFo->value,
                PersonStatus::Contractor->value,
                PersonStatus::Executive->value,
            ])
            ->count();
    }

    public function countsTowardLimit(?string $status): bool
    {
        $enum = PersonStatus::tryFrom((string) $status);

        return $enum?->clocksIn() ?? false;
    }

    public function assertSeat(Organization $organization, ?string $newStatus = null, ?Person $existing = null): void
    {
        if ($newStatus !== null && ! $this->countsTowardLimit($newStatus)) {
            return;
        }

        if ($existing && $this->countsTowardLimit($existing->status->value)) {
            return;
        }

        $limit = $this->employeeLimit($organization);
        if ($limit === null || $limit <= 0) {
            return;
        }

        if ($this->countedHeadcount($organization) >= $limit) {
            throw ValidationException::withMessages([
                'status' => 'Dosegnut je limit aktivnih osoba u paketu ('.$limit.').',
            ]);
        }
    }

    /**
     * @return array<string, bool>
     */
    public function resolved(Organization $organization): array
    {
        $defaults = OrganizationFeatures::defaultsForPlan($organization->plan);
        $stored = is_array($organization->features) ? $organization->features : [];
        $merged = $defaults;
        foreach (OrganizationFeatures::KEYS as $key) {
            if (array_key_exists($key, $stored)) {
                $merged[$key] = (bool) $stored[$key];
            }
        }

        if ($organization->volunteer_module) {
            $merged[OrganizationFeatures::VOLUNTEER_MODULE] = true;
        }

        return $merged;
    }
}
