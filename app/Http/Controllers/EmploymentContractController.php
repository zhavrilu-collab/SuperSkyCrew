<?php

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\EmploymentInstrument;
use App\Models\EmploymentContract;
use App\Models\Person;
use App\Services\EmploymentContractService;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmploymentContractController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly EmploymentContractService $contracts,
    ) {}

    public function store(Request $request, string $slug, Person $person): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'kind' => ['required', Rule::enum(EmploymentInstrument::class)],
            'contract_type' => ['nullable', Rule::enum(ContractType::class)],
            'number' => ['nullable', 'string', 'max:64'],
            'signed_at' => ['required', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'trial_ends_at' => ['nullable', 'date'],
            'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:60'],
            'gross_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'notice_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        if (($data['contract_type'] ?? null) === ContractType::FixedTerm->value) {
            $request->validate([
                'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            ]);
            $data['ends_at'] = $request->input('ends_at');
        }

        foreach (['number', 'contract_type', 'ends_at', 'trial_ends_at', 'weekly_hours', 'gross_salary', 'notice_days', 'note'] as $empty) {
            if (($data[$empty] ?? null) === '') {
                $data[$empty] = null;
            }
        }

        $data['is_current'] = $request->boolean('is_current')
            || $person->employmentContracts()->doesntExist();

        $this->contracts->store($organization, $person, $data, $request->user());

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'ugovori'])
            ->with('status', 'Ugovor je spremljen.');
    }

    public function destroy(string $slug, Person $person, EmploymentContract $employmentContract): RedirectResponse
    {
        $this->assertPerson($person);
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($employmentContract->person_id === $person->id, 404);
        abort_unless($employmentContract->organization_id === $organization->id, 404);

        $this->contracts->destroy($employmentContract);

        return redirect()
            ->route('organization.people.edit', [$organization->slug, $person, 'tab' => 'ugovori'])
            ->with('status', 'Ugovor je uklonjen.');
    }

    private function assertPerson(Person $person): void
    {
        abort_unless($person->organization_id === app('currentOrganization')->id, 404);
    }
}
