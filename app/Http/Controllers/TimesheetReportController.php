<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\ComplianceExport;
use App\Models\Person;
use App\Models\TimeEntry;
use App\Services\AuditService;
use App\Services\DepartmentScopeService;
use App\Services\OrganizationRbacService;
use App\Services\PayrollHoursService;
use App\Services\WorkFundService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimesheetReportController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly DepartmentScopeService $scope,
        private readonly WorkFundService $fund,
        private readonly PayrollHoursService $payrollHours,
        private readonly AuditService $audit,
    ) {}

    public function fund(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->authorizeTime($organization->id);
        [$month, $asOf] = $this->month($request);
        $people = $this->visiblePeople();

        return view('organization.timesheet.fund', [
            'organization' => $organization,
            'month' => $month,
            'asOf' => $asOf,
            'rows' => $this->fund->monthRows($people, $month, $asOf),
            'canPayroll' => $this->rbac->can($organization->id, (int) Auth::id(), 'payroll.export'),
        ]);
    }

    public function exportFund(Request $request): StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->authorizeTime($organization->id);
        [$month, $asOf] = $this->month($request);
        $rows = $this->fund->monthRows($this->visiblePeople(), $month, $asOf);
        $this->recordExport($organization->id, 'fund', $month->copy()->startOfMonth(), $month->copy()->endOfMonth(), AuditAction::FundExport);

        $filename = 'fond-'.$month->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Osoba', 'Tjedni sati', 'Dnevni fond min', 'Radni dani do danas', 'Radni dani mjeseca',
                'Očekivano min', 'Fond mjeseca min', 'Realizirano min', 'Evidencijski min', 'Odstupanje min', 'Iznimka',
            ], ';');
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['weekly_hours'],
                    $row['daily_minutes'],
                    $row['days_elapsed'],
                    $row['days_month'],
                    $row['expected_to_date'],
                    $row['expected_month'],
                    $row['total_minutes'],
                    $row['evidential_minutes'],
                    $row['delta'],
                    $row['flagged'] ? 'da' : '',
                ], ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function payrollHours(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'payroll.export');
        [$from, $to] = $this->range($request);
        $rows = $this->payrollHours->rows($this->payrollEntries($organization->id, $from, $to));

        return view('organization.timesheet.payroll-hours', [
            'organization' => $organization,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
        ]);
    }

    public function apiPayrollHours(Request $request): JsonResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'payroll.export');
        [$from, $to] = $this->range($request);
        $rows = $this->payrollHours->rows($this->payrollEntries($organization->id, $from, $to));

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
        ]);
    }

    public function exportPayrollHours(Request $request): StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'payroll.export');
        [$from, $to] = $this->range($request);
        $rows = $this->payrollHours->rows($this->payrollEntries($organization->id, $from, $to));
        $this->recordExport($organization->id, 'payroll_hours', $from, $to, AuditAction::PayrollHoursExport);

        $filename = 'sati-place-'.$from->toDateString().'-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Osoba', 'OIB', 'Šifra', 'Mjesto troška', 'Minute', 'Sati'], ';');
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['oib'],
                    $row['code'],
                    $row['cost_center'],
                    $row['minutes'],
                    number_format($row['hours'], 2, '.', ''),
                ], ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Collection<int, Person>
     */
    private function visiblePeople(): Collection
    {
        $organization = app('currentOrganization');
        $query = Person::query()
            ->forOrganization($organization)
            ->clockEligible()
            ->with('employmentContracts')
            ->orderBy('last_name')
            ->orderBy('first_name');
        $this->scope->restrictPeopleQuery($query, $organization, (int) Auth::id());

        return $query->get();
    }

    /**
     * @return Collection<int, TimeEntry>
     */
    private function payrollEntries(int $organizationId, Carbon $from, Carbon $to): Collection
    {
        $people = $this->visiblePeople();

        return TimeEntry::query()
            ->with(['person.costCenter', 'evidentialCostCenter'])
            ->where('organization_id', $organizationId)
            ->whereIn('person_id', $people->pluck('id'))
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->orderBy('person_id')
            ->orderBy('work_date')
            ->get();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function month(Request $request): array
    {
        $tz = config('app.timezone', 'Europe/Zagreb');
        $raw = (string) $request->input('mjesec', now()->timezone($tz)->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $raw)) {
            $raw = now()->timezone($tz)->format('Y-m');
        }
        $month = Carbon::parse($raw.'-01', $tz)->startOfMonth();
        $asOf = now()->timezone($tz)->startOfDay();
        if ((int) $asOf->format('Ym') !== (int) $month->format('Ym')) {
            $asOf = $month->copy()->endOfMonth()->startOfDay();
        }

        return [$month, $asOf];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->startOfDay();
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        return [$from, $to];
    }

    private function recordExport(int $organizationId, string $kind, Carbon $from, Carbon $to, AuditAction $action): void
    {
        ComplianceExport::query()->create([
            'organization_id' => $organizationId,
            'user_id' => Auth::id(),
            'kind' => $kind,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
        ]);

        $organization = app('currentOrganization');
        $this->audit->record(
            $organization,
            $action,
            Auth::user(),
            $action->label().' '.$from->format('d.m.Y.').' – '.$to->format('d.m.Y.'),
            null,
            ComplianceExport::class,
            null,
            ['kind' => $kind, 'from' => $from->toDateString(), 'to' => $to->toDateString()],
        );
    }

    private function authorizeTime(int $organizationId): void
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
}
