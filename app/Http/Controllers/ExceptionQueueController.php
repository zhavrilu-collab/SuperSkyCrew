<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Services\AuditService;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExceptionQueueController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->authorizeQueue($organization->id);

        $from = Carbon::parse($request->input('from', now()->subDays(14)->toDateString()))
            ->timezone(config('app.timezone'))
            ->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))
            ->timezone(config('app.timezone'))
            ->startOfDay();
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $resolved = $request->boolean('resolved');
        $userId = (int) Auth::id();
        $visibleIds = $this->visiblePersonIds($organization, $userId);

        $entries = TimeEntry::query()
            ->forOrganization($organization)
            ->with(['person', 'resolvedBy'])
            ->whereIn('person_id', $visibleIds)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->when(
                $resolved,
                fn ($query) => $query->whereNotNull('exception_code')->whereNotNull('exception_resolved_at'),
                fn ($query) => $query->openExceptions(),
            )
            ->orderByDesc('work_date')
            ->orderBy('person_id')
            ->get();

        return view('organization.exceptions.index', [
            'organization' => $organization,
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'resolved' => $resolved,
            'canResolve' => $this->rbac->can($organization->id, $userId, 'time.access'),
        ]);
    }

    public function resolve(Request $request, string $slug, TimeEntry $entry): RedirectResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'time.access');
        abort_unless($entry->organization_id === $organization->id, 404);
        abort_unless($entry->person && $this->scope->canManagePerson($entry->person, (int) Auth::id()), 403, 'Nemate ovlasti za ovu radnju.');

        if ($entry->exception_code === null) {
            return back()->withErrors(['comment' => 'Ovaj slog nema iznimke.']);
        }

        if ($entry->exception_resolved_at !== null) {
            return back()->withErrors(['comment' => 'Iznimka je već riješena.']);
        }

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:255'],
        ]);

        $entry->update([
            'exception_resolved_at' => now(),
            'exception_resolved_by' => Auth::id(),
            'exception_note' => $data['comment'],
        ]);

        $this->audit->record(
            $organization,
            AuditAction::ExceptionResolve,
            $request->user(),
            'Iznimka riješena: '.$entry->exceptionLabel().' · '.$entry->person?->fullName()
                .' '.$entry->work_date?->format('d.m.Y.').' · '.$data['comment'],
            $entry->person,
            TimeEntry::class,
            $entry->id,
            ['comment' => $data['comment']],
        );

        return back()->with('status', 'Iznimka je označena kao riješena.');
    }

    private function authorizeQueue(int $organizationId): void
    {
        $userId = (int) Auth::id();
        if (
            $this->rbac->can($organizationId, $userId, 'time.access')
            || $this->rbac->can($organizationId, $userId, 'payroll.export')
        ) {
            return;
        }

        abort(403, 'Nemate ovlasti za ovu radnju.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function visiblePersonIds($organization, int $userId)
    {
        $query = Person::query()->forOrganization($organization);
        $this->scope->restrictPeopleQuery($query, $organization, $userId);

        return $query->pluck('id');
    }
}
