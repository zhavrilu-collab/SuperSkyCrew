<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Rules\ValidOib;
use App\Services\AdminConsoleWebhookService;
use App\Services\CoreAuthService;
use App\Services\CourtRegisterLookupService;
use App\Services\HrSetupService;
use App\Services\OrganizationOnboardingService;
use App\Support\OrganizationFeatures;
use App\Support\UserOrganizationNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationRegistrationController extends Controller
{
    public function create(Request $request, CourtRegisterLookupService $courtRegister): View
    {
        $selectedPlan = old('plan', $request->query('plan', 'standard'));
        if (! in_array($selectedPlan, OrganizationFeatures::planSlugs(), true)) {
            $selectedPlan = 'standard';
        }

        $trialPlan = (string) config('subscription_plans.trial_plan', 'standard');

        return view('auth.register-organization', [
            'isLoggedIn' => Auth::check(),
            'plans' => collect(OrganizationFeatures::planSlugs())->map(fn (string $slug) => [
                'slug' => $slug,
                'name' => OrganizationFeatures::planLabel($slug),
                'summary' => OrganizationFeatures::planSummary($slug),
                'recommended' => $slug === $trialPlan,
            ]),
            'selectedPlan' => $selectedPlan,
            'trialDays' => (int) config('subscription_plans.trial_days', 14),
            'trialPlan' => $trialPlan,
            'trialPlanLabel' => OrganizationFeatures::planLabel($trialPlan),
            'courtRegisterConfigured' => $courtRegister->isConfigured(),
            'courtRegisterLookupUrl' => route('register.organization.court-register'),
            'craftsRegisterSearchUrl' => OrganizationType::CRAFTS_REGISTER_SEARCH_URL,
        ]);
    }

    public function courtRegisterLookup(Request $request, CourtRegisterLookupService $courtRegister): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        return response()->json([
            'results' => $courtRegister->search($data['q'], 8),
            'meta' => [
                'configured' => $courtRegister->isConfigured(),
            ],
        ]);
    }

    public function pending(): View|RedirectResponse
    {
        $orgUsers = UserOrganizationNavigation::organizationUsers((int) Auth::id());
        $pending = $orgUsers
            ->filter(fn (OrganizationUser $ou) => $ou->organization->status === OrganizationStatus::Pending)
            ->map(fn (OrganizationUser $ou) => $ou->organization)
            ->values();

        if ($pending->isEmpty()) {
            $path = UserOrganizationNavigation::landingPath($orgUsers);

            return $path !== null
                ? redirect($path)
                : redirect()->route('register.organization');
        }

        return view('auth.registration-pending', [
            'pendingOrganizations' => $pending,
        ]);
    }

    public function status(): JsonResponse
    {
        $orgUsers = UserOrganizationNavigation::organizationUsers((int) Auth::id());

        return response()->json([
            'pending' => UserOrganizationNavigation::hasPending($orgUsers),
            'redirect_url' => UserOrganizationNavigation::landingPath($orgUsers),
            'organizations' => $orgUsers->map(fn (OrganizationUser $ou) => [
                'name' => $ou->organization->name,
                'slug' => $ou->organization->slug,
                'status' => $ou->organization->status->value,
            ])->values(),
        ]);
    }

    public function store(
        Request $request,
        AdminConsoleWebhookService $webhook,
        CoreAuthService $coreAuth,
    ): RedirectResponse {
        $type = OrganizationType::tryFrom((string) $request->input('organization_type', OrganizationType::Company->value))
            ?? OrganizationType::Company;

        $oibRules = ['required', 'string', new ValidOib];
        if (! $type->usesCraftsRegister()) {
            $oibRules[] = Rule::unique('organizations', 'oib')->where(
                fn ($query) => $query->where('organization_type', '!=', OrganizationType::Craft->value),
            );
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'oib' => $oibRules,
            'organization_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:255'],
            'mbs' => ['nullable', 'string', 'max:32'],
            'nkd' => ['nullable', 'string', 'max:16'],
            'organization_type' => ['nullable', Rule::enum(OrganizationType::class)],
            'plan' => ['nullable', Rule::in(OrganizationFeatures::planSlugs())],
        ];

        if (! Auth::check()) {
            $rules['admin_email'] = ['required', 'email', 'max:255'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['admin_name'] = ['required', 'string', 'max:255'];

            if (! $coreAuth->isEnabled()) {
                $rules['admin_email'][] = Rule::unique('users', 'email');
            }
        }

        $data = $request->validate($rules);

        $organization = DB::transaction(function () use ($data, $coreAuth) {
            $user = Auth::user();

            if ($user === null) {
                if ($coreAuth->isEnabled()) {
                    $coreSession = $coreAuth->registerWithCredentials(
                        $data['admin_name'],
                        $data['admin_email'],
                        $data['password'],
                        $data['password_confirmation'] ?? null,
                    );
                    $user = $coreAuth->resolveLocalUser(
                        $coreSession['core_user_id'],
                        $coreSession['email'],
                        $coreSession['name'],
                    );
                    $coreAuth->storeTokenInSession($coreSession['token']);
                } else {
                    $user = User::query()->create([
                        'name' => $data['admin_name'] ?? $data['admin_email'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['password']),
                        'email_verified_at' => now(),
                    ]);
                }
            }

            $type = OrganizationType::tryFrom((string) ($data['organization_type'] ?? OrganizationType::Company->value))
                ?? OrganizationType::Company;
            $plan = (string) ($data['plan'] ?? 'standard');

            $organization = Organization::query()->create([
                'name' => $data['name'],
                'slug' => OrganizationOnboardingService::makeUniqueSlug($data['name']),
                'status' => OrganizationStatus::Pending,
                'plan' => $plan,
                'employee_limit' => OrganizationFeatures::defaultLimit($plan),
                'status_changed_at' => now(),
                'email' => $data['organization_email'],
                'oib' => preg_replace('/\s+/', '', $data['oib']),
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'street' => $data['street'] ?? null,
                'mbs' => $data['mbs'] ?? null,
                'nkd' => $data['nkd'] ?? null,
                'organization_type' => $type,
                'volunteer_module' => $type === OrganizationType::Nonprofit,
            ]);

            OrganizationUser::query()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => OrganizationRole::Owner,
            ]);

            app(HrSetupService::class)->provision($organization);

            return $organization;
        });

        $webhook->notifyTenantRegistered($organization);

        if (! Auth::check()) {
            Auth::login(User::query()->whereKey(
                OrganizationUser::query()->where('organization_id', $organization->id)->value('user_id'),
            )->firstOrFail());
            $request->session()->regenerate();
        }

        return redirect()
            ->route('registration.pending')
            ->with('status', 'Tvrtka je registrirana i čeka odobrenje administratora.');
    }
}
