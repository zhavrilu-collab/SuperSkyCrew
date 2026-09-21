<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Support\OrganizationFeatures;

class OrganizationTrialService
{
    /**
     * Pokreće trial pri prvoj aktivaciji. Plan tijekom trialja nikad ne smije
     * biti ispod trial_plan (Standardni) — čak ni ako Core pošalje basic.
     */
    public function startTrialIfNeeded(Organization $organization, ?string $preferredPlan = null): bool
    {
        if ($organization->status !== OrganizationStatus::Active) {
            return false;
        }

        if ($organization->trial_ends_at !== null) {
            return false;
        }

        if (filled($organization->stripe_subscription_id)) {
            return false;
        }

        $days = max(1, (int) config('subscription_plans.trial_days', 14));
        $organization->trial_ends_at = now()->addDays($days);
        $this->applyPlan(
            $organization,
            $this->resolveTrialPlan($preferredPlan ?? (string) $organization->plan),
        );
        $organization->save();

        return true;
    }

    public function resolveTrialPlan(string $preferredPlan): string
    {
        $trialPlan = (string) config('subscription_plans.trial_plan', 'standard');

        return $this->preferHigherPlan($preferredPlan, $trialPlan);
    }

    public function preferHigherPlan(string $a, string $b): string
    {
        return OrganizationFeatures::planSortOrder($a) >= OrganizationFeatures::planSortOrder($b)
            ? $a
            : $b;
    }

    public function extendTrial(Organization $organization, int $days): Organization
    {
        $days = max(1, $days);

        if (filled($organization->stripe_subscription_id)) {
            throw new \RuntimeException(
                'Probni period se ne može produljiti dok postoji aktivna Stripe pretplata.',
            );
        }

        $base = $organization->trial_ends_at !== null && $organization->trial_ends_at->isFuture()
            ? $organization->trial_ends_at->copy()
            : now();

        $organization->trial_ends_at = $base->addDays($days);
        $this->applyPlan($organization, $this->resolveTrialPlan((string) $organization->plan));
        $organization->save();

        return $organization;
    }

    public function expireIfNeeded(Organization $organization): bool
    {
        if ($organization->trial_ends_at === null) {
            return false;
        }

        if ($organization->trial_ends_at->isFuture()) {
            return false;
        }

        if (filled($organization->stripe_subscription_id)) {
            return false;
        }

        $fallback = (string) config('subscription_plans.trial_fallback_plan', 'basic');
        if ((string) $organization->plan === $fallback) {
            return false;
        }

        $this->applyPlan($organization, $fallback);
        $organization->save();

        return true;
    }

    public function expireAllDue(): int
    {
        $count = 0;

        Organization::query()
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('stripe_subscription_id')
                    ->orWhere('stripe_subscription_id', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($organizations) use (&$count): void {
                foreach ($organizations as $organization) {
                    if ($this->expireIfNeeded($organization)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function applyPlan(Organization $organization, string $plan): void
    {
        $organization->plan = $plan;
        $organization->employee_limit = OrganizationFeatures::defaultLimit($plan);
    }
}
