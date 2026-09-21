<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\FeatureService;
use App\Services\OrganizationTrialService;
use App\Support\OrganizationFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationSyncController extends Controller
{
    public function __construct(
        private readonly OrganizationTrialService $trialService,
        private readonly FeatureService $features,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updated_since' => ['nullable', 'date'],
        ]);

        $query = Organization::query()->orderBy('id');

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', $validated['updated_since']);
        }

        $organizations = $query->get()->map(fn (Organization $organization) => $this->resource($organization));

        return response()->json([
            'data' => $organizations,
            'meta' => [
                'total' => $organizations->count(),
            ],
        ]);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'required', Rule::enum(OrganizationStatus::class)],
            'plan' => ['sometimes', 'required', 'string', Rule::in(OrganizationFeatures::planSlugs())],
            'employee_limit' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10000'],
            'features' => ['sometimes', 'nullable', 'array'],
            'stripe_customer_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_subscription_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'extend_trial_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
        ], [
            'extend_trial_days.required' => 'Unesite broj dana za produljenje probnog perioda.',
            'extend_trial_days.integer' => 'Broj dana mora biti cijeli broj.',
            'extend_trial_days.min' => 'Probni period mora biti produljen za najmanje 1 dan.',
            'extend_trial_days.max' => 'Probni period se može produljiti najviše za 365 dana.',
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'Potrebno je poslati status, plan, Stripe podatke ili produljenje triala.',
            ], 422);
        }

        $previousStatus = $organization->status;

        if (array_key_exists('status', $validated)) {
            $organization->status = $validated['status'];
            $organization->status_changed_at = now();
        }

        if (array_key_exists('plan', $validated)) {
            $organization->plan = $validated['plan'];
        }

        if (array_key_exists('employee_limit', $validated)) {
            $organization->employee_limit = $validated['employee_limit'];
        }

        if (array_key_exists('features', $validated)) {
            $organization->features = $validated['features'];
        }

        if (array_key_exists('stripe_customer_id', $validated)) {
            $organization->stripe_customer_id = $validated['stripe_customer_id'];
        }

        if (array_key_exists('stripe_subscription_id', $validated)) {
            $organization->stripe_subscription_id = $validated['stripe_subscription_id'];
        }

        $organization->save();

        $becameActive = array_key_exists('status', $validated)
            && $organization->status === OrganizationStatus::Active
            && $previousStatus !== OrganizationStatus::Active;

        if ($becameActive) {
            $this->trialService->startTrialIfNeeded(
                $organization,
                preferredPlan: (string) $organization->plan,
            );
            $organization->refresh();
        }

        if (array_key_exists('extend_trial_days', $validated)) {
            try {
                $this->trialService->extendTrial($organization, (int) $validated['extend_trial_days']);
            } catch (\RuntimeException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }
            $organization->refresh();
        }

        return response()->json([
            'data' => $this->resource($organization->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status->value,
            'plan' => $organization->plan,
            'employee_limit' => $organization->employee_limit,
            'features' => $this->features->resolved($organization),
            'email' => $organization->email,
            'oib' => $organization->oib,
            'stripe_customer_id' => $organization->stripe_customer_id,
            'stripe_subscription_id' => $organization->stripe_subscription_id,
            'trial_ends_at' => $organization->trial_ends_at?->toIso8601String(),
            'created_at' => $organization->created_at?->toIso8601String(),
            'updated_at' => $organization->updated_at?->toIso8601String(),
        ];
    }
}
