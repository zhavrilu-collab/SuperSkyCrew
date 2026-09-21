<?php

namespace App\Http\Controllers;

use App\Support\OrganizationFeatures;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (Auth::check()) {
            $path = UserOrganizationNavigation::postAuthRedirectPath(
                UserOrganizationNavigation::organizationUsers((int) Auth::id()),
            );

            return $path !== null
                ? redirect($path)
                : redirect()->route('register.organization');
        }

        $trialPlan = (string) config('subscription_plans.trial_plan', 'standard');

        return view('welcome', [
            'trialDays' => (int) config('subscription_plans.trial_days', 14),
            'trialPlanLabel' => OrganizationFeatures::planLabel($trialPlan),
        ]);
    }
}
