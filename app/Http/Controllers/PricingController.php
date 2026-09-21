<?php

namespace App\Http\Controllers;

use App\Support\OrganizationFeatures;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(): View
    {
        $trialPlan = (string) config('subscription_plans.trial_plan', 'standard');

        $plans = collect(OrganizationFeatures::planSlugs())->map(fn (string $slug) => [
            'slug' => $slug,
            'name' => OrganizationFeatures::planLabel($slug),
            'summary' => OrganizationFeatures::planSummary($slug),
            'recommended' => $slug === $trialPlan,
            'highlights' => match ($slug) {
                'basic' => ['Šihterica (PWA)', 'Dosjei i ugovori', 'Ustroj tvrtke', 'Radni slijed'],
                'standard' => ['Sve iz Osnovnog', 'Kiosk i geofence', 'Plan smjena', 'Inspekcijski izvoz'],
                'premium' => ['Sve iz Standardnog', 'Volonteri', 'Veći limit kadra'],
                default => [],
            },
        ]);

        return view('pricing', [
            'plans' => $plans,
            'trialDays' => (int) config('subscription_plans.trial_days', 14),
            'trialPlanLabel' => OrganizationFeatures::planLabel($trialPlan),
        ]);
    }
}
