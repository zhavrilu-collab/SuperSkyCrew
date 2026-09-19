<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\CalendarLevel;
use App\Enums\ClockChannel;
use App\Enums\DeviceBindMode;
use App\Enums\GeofenceMode;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Enums\RetentionClass;
use App\Models\AbsenceCode;
use App\Models\CalendarRule;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\JobPosition;
use App\Models\LeaveTenureRule;
use App\Models\Location;
use App\Models\OrganizationUser;
use App\Models\Person;
use App\Models\PersonDocument;
use App\Models\ReminderSend;
use App\Models\Shift;
use App\Models\StaffInvite;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\AuditService;
use App\Services\DepartmentScopeService;
use App\Services\HrSetupService;
use App\Services\LeaveService;
use App\Services\OrganizationRbacService;
use App\Services\PeopleImportService;
use App\Services\RetentionService;
use App\Support\DocumentMergeFields;
use App\Support\OrganizationThemes;
use App\Support\SettingsCatalog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
        private readonly HrSetupService $setup,
        private readonly PeopleImportService $import,
        private readonly RetentionService $retention,
        private readonly AuditService $audit,
        private readonly LeaveService $leave,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $userId = (int) Auth::id();
        $this->assertCanOpen($organization->id, $userId);

        $isOwner = $this->rbac->isOwner($organization->id, $userId);
        $canHr = $this->rbac->can($organization->id, $userId, 'people.access');
        $canAccess = $this->rbac->can($organization->id, $userId, 'time.access')
            || $this->rbac->can($organization->id, $userId, 'people.access')
            || $this->rbac->can($organization->id, $userId, 'payroll.export');
        $canTeam = $this->rbac->can($organization->id, $userId, 'team.manage');

        $tabs = SettingsCatalog::catalogTabs($isOwner, $canHr, $canAccess, $canTeam);
        abort_if($tabs === [], 403, 'Nemate ovlasti za ovu radnju.');

        $tab = (string) $request->query('tab', $tabs[0]['key']);
        if (! $this->rbac->canAccessSettingsTab($organization->id, $userId, $tab)) {
            $tab = $tabs[0]['key'];
        }

        $section = SettingsCatalog::resolveSection($tab, (string) $request->query('section', ''), $isOwner);
        $sections = SettingsCatalog::sectionsFor($tab, $isOwner);

        return view('organization.settings.index', array_merge([
            'organization' => $organization,
            'tab' => $tab,
            'section' => $section,
            'sections' => $sections,
            'catalogTabs' => $tabs,
            'isOwner' => $isOwner,
            'canHr' => $canHr,
            'themes' => OrganizationThemes::builtinAll(),
            'permissions' => $this->rbacPermissions(),
        ], $this->sectionData($request, $tab, $section)));
    }

    public function updateOrganization(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'settings.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_email' => ['required', 'email', 'max:255'],
            'oib' => ['required', 'string', 'max:11'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'organization_type' => ['required', Rule::enum(OrganizationType::class)],
        ]);

        $organization->update([
            'name' => $data['name'],
            'email' => $data['organization_email'],
            'oib' => preg_replace('/\s+/', '', $data['oib']),
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'organization_type' => $data['organization_type'],
        ]);

        return back()->with('status', 'Podaci organizacije su spremljeni.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'settings.manage');

        $data = $request->validate([
            'theme_key' => ['required', Rule::in(OrganizationThemes::keys())],
        ]);

        $organization->update(['theme_key' => $data['theme_key']]);

        return back()->with('status', 'Izgled je spremljen.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'settings.manage');

        if ($request->boolean('remove')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $organization->update(['logo_path' => null]);

            return back()->with('status', 'Logotip je uklonjen.');
        }

        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $path = $request->file('logo')->store('organization-logos', 'public');
        if ($organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
        }
        $organization->update(['logo_path' => $path]);

        return back()->with('status', 'Logotip je spremljen.');
    }

    public function updateVolunteer(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $organization->update([
            'volunteer_module' => $request->boolean('volunteer_module'),
        ]);

        return back()->with('status', 'Postavka volontera je spremljena.');
    }

    public function updateExpiry(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'expiry_warning_days' => ['required', 'integer', Rule::in([5, 10, 20, 30])],
        ]);
        $organization->update($data);

        return back()->with('status', 'Pragovi isteka su spremljeni.');
    }

    public function confirmRetention(string $slug, PersonDocument $document): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($document->organization_id === $organization->id, 404);
        abort_unless($document->isProposed(), 422, 'Dokument nije predložen za brisanje.');

        $actor = Auth::user();
        abort_unless($actor instanceof User, 403, 'Nemate ovlasti za ovu radnju.');
        $this->retention->confirm($document, $actor);

        return redirect()
            ->route('organization.settings.index', [
                'slug' => $organization->slug,
                'tab' => 'kadar',
                'section' => 'zadrzavanje',
            ])
            ->with('status', 'Datoteka je uklonjena. Zapis ostaje kao trag čuvanja.');
    }

    public function storeDocumentType(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $request->merge([
            'code' => strtolower((string) $request->input('code')),
        ]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('document_types', 'code')->where('organization_id', $organization->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'retention_class' => ['required', Rule::enum(RetentionClass::class)],
        ]);

        $maxSort = (int) DocumentType::query()->forOrganization($organization)->max('sort_order');

        DocumentType::query()->create([
            'organization_id' => $organization->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'retention_class' => $data['retention_class'],
            'tracks_expiry' => $request->boolean('tracks_expiry'),
            'is_system' => false,
            'sort_order' => $maxSort + 10,
        ]);

        return back()->with('status', 'Vrsta dokumenta je dodana.');
    }

    public function destroyDocumentType(string $slug, DocumentType $documentType): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($documentType->organization_id === $organization->id, 404);
        abort_if($documentType->is_system, 422, 'Sistemske vrste se ne brišu.');
        abort_if($documentType->documents()->exists(), 422, 'Vrsta se koristi na kartici osobe.');
        $documentType->delete();

        return back()->with('status', 'Vrsta dokumenta je obrisana.');
    }

    public function storeDocumentTemplate(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'document_type_id' => [
                'nullable',
                Rule::exists('document_types', 'id')->where('organization_id', $organization->id),
            ],
            'file' => ['required', 'file', 'extensions:pdf,doc,docx', 'max:5120'],
        ]);

        $file = $request->file('file');

        DocumentTemplate::query()->create([
            'organization_id' => $organization->id,
            'document_type_id' => $data['document_type_id'] ?? null,
            'name' => $data['name'],
            'file_path' => $file->store('document-templates/'.$organization->id, 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
        ]);

        return back()->with('status', 'Predložak je spremljen.');
    }

    public function downloadDocumentTemplate(string $slug, DocumentTemplate $template): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($template->organization_id === $organization->id, 404);
        abort_unless(Storage::disk('local')->exists($template->file_path), 404);

        return Storage::disk('local')->download($template->file_path, $template->original_name);
    }

    public function destroyDocumentTemplate(string $slug, DocumentTemplate $template): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($template->organization_id === $organization->id, 404);
        abort_if($template->is_system, 422, 'Ugrađeni predlošci se ne brišu.');
        $template->deleteFile();
        $template->delete();

        return back()->with('status', 'Predložak je obrisan.');
    }

    public function importPeople(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
        ]);

        $result = $this->import->import($organization, $request->file('file'));
        $this->audit->record(
            $organization,
            AuditAction::PeopleImport,
            $request->user(),
            'Uvoz kadra: '.$result['created'].' novih, '.$result['updated'].' ažuriranih'
                .($result['skipped'] !== [] ? ', preskočeno '.count($result['skipped']) : ''),
            null,
            null,
            null,
            ['created' => $result['created'], 'updated' => $result['updated'], 'skipped' => count($result['skipped'])],
        );
        $message = 'Uvezeno: '.$result['created'].' novih, '.$result['updated'].' ažuriranih.';
        if ($result['skipped'] !== []) {
            $message .= ' Preskočeno: '.count($result['skipped']).'.';
        }

        return back()
            ->with('status', $message)
            ->with('import_skipped', $result['skipped']);
    }

    public function importPeopleTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $headers = $this->import->headers();

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');
            fputcsv($handle, ['Ana', 'Horvat', '', 'z', '15.03.1990', 'HR', 'Zagreb', 'employee', 'indefinite', '01.01.2024', '', 'Referentica'], ';');
            fclose($handle);
        }, 'uvoz-kadra.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function storeWorkflowStep(Request $request, string $slug, Workflow $workflow): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($workflow->organization_id === $organization->id, 404);

        $data = $request->validate([
            'role' => ['required', Rule::in([
                OrganizationRole::Manager->value,
                OrganizationRole::Hr->value,
                OrganizationRole::Owner->value,
            ])],
            'min_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'max_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);

        if (($data['min_days'] ?? null) !== null && ($data['max_days'] ?? null) !== null && $data['min_days'] > $data['max_days']) {
            return back()->withErrors(['max_days' => 'Gornji prag ne smije biti manji od donjeg.']);
        }

        $position = (int) $workflow->steps()->max('position') + 1;

        WorkflowStep::query()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'position' => $position,
            'role' => $data['role'],
            'min_days' => $data['min_days'] !== null && $data['min_days'] !== '' ? (int) $data['min_days'] : null,
            'max_days' => $data['max_days'] !== null && $data['max_days'] !== '' ? (int) $data['max_days'] : null,
        ]);

        return back()->with('status', 'Korak je dodan.');
    }

    public function destroyWorkflowStep(string $slug, Workflow $workflow, WorkflowStep $step): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($workflow->organization_id === $organization->id, 404);
        abort_unless($step->workflow_id === $workflow->id, 404);
        abort_unless($step->organization_id === $organization->id, 404);
        abort_if($workflow->steps()->count() <= 1, 422, 'Slijed mora imati barem jedan korak.');
        $step->delete();

        return back()->with('status', 'Korak je uklonjen.');
    }

    public function updateLeavePolicy(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'annual_leave_base_days' => ['required', 'integer', 'min:0', 'max:50'],
            'annual_leave_days_per_child' => ['required', 'integer', 'min:0', 'max:10'],
        ]);
        $organization->update($data);

        return back()->with('status', 'Politika GO je spremljena.');
    }

    public function storeTenureRule(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'min_years' => [
                'required',
                'integer',
                'min:0',
                'max:50',
                Rule::unique('leave_tenure_rules', 'min_years')->where('organization_id', $organization->id),
            ],
            'extra_days' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $data['organization_id'] = $organization->id;
        LeaveTenureRule::query()->create($data);

        return back()->with('status', 'Prag staža je dodan.');
    }

    public function destroyTenureRule(string $slug, LeaveTenureRule $rule): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($rule->organization_id === $organization->id, 404);
        $rule->delete();

        return back()->with('status', 'Prag staža je uklonjen.');
    }

    public function recalculateLeave(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $count = $this->leave->recalculateOrganization($organization);

        return back()->with('status', 'Preračunato je '.$count.' fondova GO (bez ručnih kartica).');
    }

    public function updatePeriodLock(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'period_lock_day' => ['required', 'integer', 'min:0', 'max:28'],
        ]);
        $organization->update([
            'period_lock_day' => (int) $data['period_lock_day'],
            'show_clock_bounds' => $request->boolean('show_clock_bounds'),
        ]);

        return back()->with('status', 'Kalendar zaključavanja i izvještaj su spremljeni.');
    }

    public function storeAbsenceCode(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $request->merge([
            'code' => strtoupper((string) $request->input('code')),
        ]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:16',
                Rule::unique('absence_codes', 'code')->where('organization_id', $organization->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'meaning' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:presence,leave,sick,holiday,other'],
        ]);

        AbsenceCode::query()->create([
            'organization_id' => $organization->id,
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'meaning' => $data['meaning'],
            'category' => $data['category'],
            'kind' => $data['category'] === 'presence' ? 'presence' : 'absence',
            'paid' => $request->boolean('paid'),
            'consumes_annual_leave' => $request->boolean('consumes_annual_leave'),
            'is_system' => false,
        ]);

        return back()->with('status', 'Šifra je dodana.');
    }

    public function destroyAbsenceCode(string $slug, AbsenceCode $code): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($code->organization_id === $organization->id, 404);
        abort_if($code->is_system, 422, 'Sistemske šifre se ne brišu.');
        $code->delete();

        return back()->with('status', 'Šifra je obrisana.');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'geofence_mode' => ['required', Rule::enum(GeofenceMode::class)],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'device_bind_mode' => ['nullable', Rule::enum(DeviceBindMode::class)],
            'punch_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            'punch_round_minutes' => ['nullable', 'integer', 'min:0', 'max:30'],
            'allowed_channels' => ['nullable', 'array'],
            'allowed_channels.*' => ['string', Rule::enum(ClockChannel::class)],
        ]);

        Location::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'geofence_mode' => $data['geofence_mode'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'radius_meters' => $data['radius_meters'] ?? 150,
            'kiosk_enabled' => $request->boolean('kiosk_enabled'),
            'kiosk_token' => $request->boolean('kiosk_enabled') ? Str::random(32) : null,
            'require_photo' => $request->boolean('require_photo'),
            'allow_offline' => $request->has('allow_offline') ? $request->boolean('allow_offline') : true,
            'device_bind_mode' => $request->input('device_bind_mode', DeviceBindMode::Off->value),
            'punch_grace_minutes' => (int) ($data['punch_grace_minutes'] ?? 5),
            'punch_round_minutes' => (int) ($data['punch_round_minutes'] ?? 0),
            'allowed_channels' => $data['allowed_channels'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('status', 'Lokacija je spremljena.');
    }

    public function updateLocation(Request $request, string $slug, Location $location): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($location->organization_id === $organization->id, 404);

        $data = $request->validate([
            'geofence_mode' => ['required', Rule::enum(GeofenceMode::class)],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'device_bind_mode' => ['nullable', Rule::enum(DeviceBindMode::class)],
            'punch_grace_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            'punch_round_minutes' => ['nullable', 'integer', 'min:0', 'max:30'],
            'allowed_channels' => ['nullable', 'array'],
            'allowed_channels.*' => ['string', Rule::enum(ClockChannel::class)],
        ]);

        $location->geofence_mode = $data['geofence_mode'];
        $location->radius_meters = $data['radius_meters'] ?? $location->radius_meters;
        $location->kiosk_enabled = $request->boolean('kiosk_enabled');
        $location->require_photo = $request->boolean('require_photo');
        $location->allow_offline = $request->boolean('allow_offline');
        $location->device_bind_mode = $data['device_bind_mode'] ?? DeviceBindMode::Off;
        $location->punch_grace_minutes = (int) ($data['punch_grace_minutes'] ?? 5);
        $location->punch_round_minutes = (int) ($data['punch_round_minutes'] ?? 0);
        $location->allowed_channels = $data['allowed_channels'] ?? null;
        if ($location->kiosk_enabled && blank($location->kiosk_token)) {
            $location->kiosk_token = Str::random(32);
        }
        $location->save();

        return back()->with('status', 'Lokacija je ažurirana.');
    }

    public function kioskPoster(string $slug, Location $location): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($location->organization_id === $organization->id, 404);
        $location->setRelation('organization', $organization);
        $url = $location->kioskUrl();
        abort_unless($url, 404);

        return view('organization.settings.kiosk-poster', [
            'organization' => $organization,
            'location' => $location,
            'kioskUrl' => $url,
        ]);
    }

    public function entrancePoster(string $slug, Location $location): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        abort_unless($location->organization_id === $organization->id, 404);
        $location->setRelation('organization', $organization);
        $url = $location->entranceUrl();
        abort_unless($url, 404);

        return view('organization.settings.entrance-poster', [
            'organization' => $organization,
            'location' => $location,
            'entranceUrl' => $url,
        ]);
    }

    private function assertCanOpen(int $organizationId, int $userId): void
    {
        if (! $this->rbac->canOpenSettings($organizationId, $userId)) {
            abort(403, 'Nemate ovlasti za ovu radnju.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionData(Request $request, string $tab, string $section): array
    {
        $organization = app('currentOrganization');

        if ($tab === SettingsCatalog::TAB_KADAR || $tab === SettingsCatalog::TAB_ODOBRENJA || $tab === SettingsCatalog::TAB_VRIJEME) {
            $this->setup->provision($organization);
        }

        if ($tab === SettingsCatalog::TAB_ORGANIZACIJA && $section === 'ustroj') {
            $on = Carbon::parse(
                filled($request->input('na')) ? $request->input('na') : now()->toDateString(),
                config('app.timezone')
            )->startOfDay();
            $katalog = in_array($request->input('katalog'), ['shema', 'odjeli', 'mjesta', 'troskovi'], true)
                ? (string) $request->input('katalog')
                : 'shema';
            $departments = $this->scope->departmentsOn($organization, $on);
            $positions = $this->scope->positionsOn($organization, $on);
            $costCenters = $this->scope->costCentersOn($organization, $on);
            $q = trim((string) $request->input('q', ''));
            $selectedPosition = null;
            if ($katalog === 'mjesta') {
                $mjestoId = (int) $request->input('mjesto');
                $selectedPosition = $mjestoId > 0
                    ? $positions->firstWhere('id', $mjestoId)
                    : $positions->first();
            }

            return [
                'on' => $on,
                'katalog' => $katalog,
                'q' => $q,
                'selectedPosition' => $selectedPosition,
                'departments' => $departments,
                'departmentRows' => \App\Support\DepartmentTree::flatten($departments),
                'positions' => $positions,
                'costCenters' => $costCenters,
                'people' => Person::query()->forOrganization($organization)->with(['employmentContracts'])->orderBy('last_name')->get(),
                'managers' => OrganizationUser::query()
                    ->with('user')
                    ->where('organization_id', $organization->id)
                    ->whereIn('role', ['owner', 'hr', 'manager'])
                    ->get()
                    ->map(fn (OrganizationUser $membership) => $membership->user)
                    ->filter()
                    ->unique('id')
                    ->values(),
            ];
        }

        if ($tab === SettingsCatalog::TAB_KADAR) {
            $data = [
                'documentTypes' => DocumentType::query()->forOrganization($organization)->orderBy('sort_order')->orderBy('name')->get(),
                'documentTemplates' => DocumentTemplate::query()->forOrganization($organization)->with('documentType')->orderBy('name')->get(),
                'retentionClasses' => RetentionClass::cases(),
                'expiryWindows' => [5, 10, 20, 30],
                'mergeFields' => DocumentMergeFields::labels(),
                'proposedDocuments' => collect(),
                'disposedDocuments' => collect(),
            ];
            if ($section === 'zadrzavanje') {
                $this->retention->proposeDue($organization);
                $data['proposedDocuments'] = $this->retention->proposed($organization);
                $data['disposedDocuments'] = $this->retention->recentlyDisposed($organization);
            }

            return $data;
        }

        if ($tab === SettingsCatalog::TAB_VRIJEME) {
            $data = [
                'absenceCodes' => AbsenceCode::query()->forOrganization($organization)->orderBy('code')->get(),
                'shifts' => Shift::query()->forOrganization($organization)->orderBy('name')->get(),
                'rules' => CalendarRule::query()->forOrganization($organization)->with(['shift', 'department', 'jobPosition', 'person'])->orderBy('level')->get(),
                'locations' => Location::query()->forOrganization($organization)->orderBy('name')->get(),
                'departments' => Department::query()->forOrganization($organization)->orderBy('name')->get(),
                'positions' => JobPosition::query()->forOrganization($organization)->orderBy('name')->get(),
                'people' => Person::query()->forOrganization($organization)->orderBy('last_name')->get(),
                'levels' => CalendarLevel::cases(),
                'geofenceModes' => GeofenceMode::cases(),
                'deviceBindModes' => DeviceBindMode::cases(),
                'clockChannels' => ClockChannel::cases(),
                'weekdayNames' => [1 => 'Ponedjeljak', 2 => 'Utorak', 3 => 'Srijeda', 4 => 'Četvrtak', 5 => 'Petak', 6 => 'Subota', 7 => 'Nedjelja'],
                'reminderTodayCount' => ReminderSend::query()
                    ->forOrganization($organization)
                    ->whereDate('sent_at', now()->timezone(config('app.timezone'))->toDateString())
                    ->count(),
                'leaveTenureRules' => LeaveTenureRule::query()->forOrganization($organization)->orderBy('min_years')->get(),
                'leaveBalances' => collect(),
            ];
            if ($section === 'go-politika') {
                $year = (int) now()->timezone(config('app.timezone'))->year;
                $data['leaveBalances'] = Person::query()
                    ->forOrganization($organization)
                    ->with(['jobPosition', 'leaveBalances' => fn ($query) => $query->where('year', $year)])
                    ->whereIn('status', [
                        \App\Enums\PersonStatus::Employee->value,
                        \App\Enums\PersonStatus::Assigned->value,
                        \App\Enums\PersonStatus::Executive->value,
                    ])
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get()
                    ->map(function (Person $person) use ($year) {
                        return [
                            'person' => $person,
                            'leave' => $this->leave->snapshot($person, $year),
                        ];
                    });
            }

            return $data;
        }

        if ($tab === SettingsCatalog::TAB_ODOBRENJA) {
            return [
                'workflows' => Workflow::query()->forOrganization($organization)->with('steps')->orderBy('name')->get(),
                'stepRoles' => [
                    OrganizationRole::Manager,
                    OrganizationRole::Hr,
                    OrganizationRole::Owner,
                ],
            ];
        }

        if ($tab === SettingsCatalog::TAB_PRISTUP) {
            return [
                'members' => OrganizationUser::query()->with('user')->where('organization_id', $organization->id)->orderBy('role')->get(),
                'pendingInvites' => StaffInvite::query()
                    ->where('organization_id', $organization->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->orderByDesc('created_at')
                    ->get(),
                'roles' => OrganizationRole::cases(),
            ];
        }

        if ($tab === SettingsCatalog::TAB_PODACI && $section === 'trag') {
            $from = Carbon::parse(
                filled($request->input('od')) ? $request->input('od') : now()->subDays(30)->toDateString(),
                config('app.timezone')
            )->startOfDay();
            $to = Carbon::parse(
                filled($request->input('do')) ? $request->input('do') : now()->toDateString(),
                config('app.timezone')
            )->endOfDay();
            if ($to->lt($from)) {
                $to = $from->copy()->endOfDay();
            }
            $action = (string) $request->input('radnja', '');
            $personId = (int) $request->input('osoba', 0);
            $events = $this->audit->queryFor($organization)
                ->where('created_at', '>=', $from)
                ->where('created_at', '<=', $to)
                ->when(
                    $action !== '' && AuditAction::tryFrom($action) !== null,
                    fn ($query) => $query->where('action', $action)
                )
                ->when($personId > 0, fn ($query) => $query->where('person_id', $personId))
                ->orderByDesc('id')
                ->limit(200)
                ->get();

            return [
                'auditFrom' => $from,
                'auditTo' => $to,
                'auditAction' => $action,
                'auditPersonId' => $personId,
                'auditEvents' => $events,
                'auditActions' => AuditAction::cases(),
                'auditPeople' => Person::query()->forOrganization($organization)->orderBy('last_name')->orderBy('first_name')->get(),
            ];
        }

        return [];
    }

    /**
     * @return list<array{permission: string, label: string, roles: string}>
     */
    private function rbacPermissions(): array
    {
        return [
            ['permission' => 'settings.manage', 'label' => 'Postavke organizacije', 'roles' => 'Vlasnik'],
            ['permission' => 'team.manage', 'label' => 'Tim i pozivnice', 'roles' => 'Vlasnik, HR'],
            ['permission' => 'people.access', 'label' => 'Kadrovi', 'roles' => 'Vlasnik, HR'],
            ['permission' => 'time.access', 'label' => 'Šihterica i iznimke', 'roles' => 'Vlasnik, HR, Voditelj'],
            ['permission' => 'payroll.export', 'label' => 'Izvoz za plaće', 'roles' => 'Vlasnik, HR, Računovodstvo'],
            ['permission' => 'time.lock', 'label' => 'Zaključavanje razdoblja', 'roles' => 'Vlasnik, HR'],
            ['permission' => 'inspection.export', 'label' => 'Inspekcijski izvoz', 'roles' => 'Vlasnik, HR'],
            ['permission' => 'requests.approve', 'label' => 'Odobrenja zahtjeva', 'roles' => 'Vlasnik, HR, Voditelj'],
        ];
    }
}
