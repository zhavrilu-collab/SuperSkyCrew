<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Person;
use App\Services\DocumentFillService;
use App\Services\DocumentInboxService;
use App\Services\FeatureService;
use App\Services\OrganizationRbacService;
use App\Support\OrganizationFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentCreatorController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DocumentFillService $fill,
        private readonly FeatureService $features,
        private readonly DocumentInboxService $inbox,
    ) {}

    public function index(): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        return view('organization.documents.creator', [
            'organization' => $organization,
            'people' => Person::query()->forOrganization($organization)->orderBy('last_name')->orderBy('first_name')->get(),
            'templates' => DocumentTemplate::query()->forOrganization($organization)->orderBy('name')->get(),
            'enabled' => $this->features->enabled($organization, OrganizationFeatures::DOCUMENT_TEMPLATES),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $this->features->assertEnabled($organization, OrganizationFeatures::DOCUMENT_TEMPLATES);

        $data = $request->validate([
            'person_id' => [
                'required',
                Rule::exists('people', 'id')->where('organization_id', $organization->id),
            ],
            'template_id' => [
                'required',
                Rule::exists('document_templates', 'id')->where('organization_id', $organization->id),
            ],
        ]);

        $person = Person::query()->forOrganization($organization)->findOrFail($data['person_id']);
        $template = DocumentTemplate::query()->forOrganization($organization)->findOrFail($data['template_id']);
        $document = $this->fill->storeFilled($template, $person);
        $this->inbox->notifyNew($organization, $person->loadMissing('user'), $document);

        return redirect()
            ->route('organization.document-creator.index', $organization->slug)
            ->with('status', 'Akt je spremljen u dosje: '.$document->title);
    }
}
