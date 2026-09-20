@extends('layouts.organization')

@section('title', 'Ugovori o radu')
@section('nav-suffix', 'Zaposlenici')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Ugovori o radu</h1>
        <p class="text-muted mb-0">Registar ugovora i aneksa. Novi slog dodaje se na kartici osobe.</p>
    </div>
    <a href="{{ route('organization.people.index', $organization->slug) }}" class="btn btn-outline-secondary">Dosjei</a>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Isprava</th>
                    <th>Broj</th>
                    <th>Važenje</th>
                    <th>Probni</th>
                    <th>Bruto</th>
                    <th>Otkazni rok</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($contracts as $contract)
                    <tr>
                        <td>
                            @if($contract->person)
                                <a href="{{ route('organization.people.edit', [$organization->slug, $contract->person, 'tab' => 'ugovori']) }}">{{ $contract->person->fullName() }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $contract->kind->label() }}{{ $contract->is_current ? ' · važeći' : '' }}</td>
                        <td>{{ $contract->number ?: '—' }}</td>
                        <td>{{ $contract->starts_at->format('d.m.Y.') }}{{ $contract->ends_at ? ' – '.$contract->ends_at->format('d.m.Y.') : '' }}</td>
                        <td>{{ $contract->trial_ends_at?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $contract->gross_salary !== null ? number_format((float) $contract->gross_salary, 2, ',', '.').' €' : '—' }}</td>
                        <td>{{ $contract->notice_days !== null ? $contract->notice_days.' d.' : '—' }}</td>
                        <td class="text-end">
                            @if($contract->person)
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('organization.people.edit', [$organization->slug, $contract->person, 'tab' => 'ugovori']) }}">Kartica</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">Nema ugovora u registru.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
