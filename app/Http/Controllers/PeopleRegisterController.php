<?php

namespace App\Http\Controllers;

use App\Services\OrganizationRbacService;
use App\Services\PeopleRegisterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleRegisterController extends Controller
{
    public function __construct(
        private readonly OrganizationRbacService $rbac,
        private readonly PeopleRegisterService $register,
    ) {}

    public function book(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $on = Carbon::parse($request->input('na', now()->toDateString()), config('app.timezone'))->startOfDay();
        $people = $this->register->activeOn($organization, $on);

        return view('organization.people.book', [
            'organization' => $organization,
            'on' => $on,
            'people' => $people,
            'exporter' => Auth::user(),
            'exportedAt' => now(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');
        $on = Carbon::parse($request->input('na', now()->toDateString()), config('app.timezone'))->startOfDay();
        $people = $this->register->activeOn($organization, $on);
        $rows = $this->register->csvRows($people);
        $filename = 'maticna-knjiga-'.$on->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->register->csvHeaders(), ';');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function turnover(Request $request): View
    {
        $organization = app('currentOrganization');
        $this->rbac->authorize($organization->id, (int) Auth::id(), 'people.access');

        $from = Carbon::parse($request->input('from', now()->startOfYear()->toDateString()), config('app.timezone'))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()), config('app.timezone'))->startOfDay();
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $hires = $this->register->hires($organization, $from, $to);
        $exits = $this->register->exits($organization, $from, $to);
        $startCount = $this->register->activeOn($organization, $from)->count();
        $endCount = $this->register->activeOn($organization, $to)->count();
        $rate = $startCount > 0 ? round(($exits->count() / $startCount) * 100, 1) : null;

        return view('organization.people.turnover', [
            'organization' => $organization,
            'from' => $from,
            'to' => $to,
            'hires' => $hires,
            'exits' => $exits,
            'startCount' => $startCount,
            'endCount' => $endCount,
            'rate' => $rate,
        ]);
    }
}
