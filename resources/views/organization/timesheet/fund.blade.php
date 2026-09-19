@extends('layouts.organization')

@section('title', 'Mjesečni fond sati')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Mjesečni fond sati</h1>
        <p class="text-muted mb-0">
            Ugovoreni tjedni sati (inače 40) × radni dani. Odstupanje = evidencijski sati minus fond do {{ $asOf->format('d.m.Y.') }}
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2">
            <input type="month" class="form-control" name="mjesec" value="{{ $month->format('Y-m') }}">
            <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
        </form>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.fund-export', [$organization->slug, 'mjesec' => $month->format('Y-m')]) }}">Izvoz CSV</a>
        @if($canPayroll)
            <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.payroll-hours', [$organization->slug, 'from' => $month->copy()->startOfMonth()->toDateString(), 'to' => $asOf->toDateString()]) }}">Sati za plaće</a>
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('organization.timesheet.index', [$organization->slug, 'from' => $month->copy()->startOfMonth()->toDateString(), 'to' => $asOf->toDateString()]) }}">Šihterica</a>
    </div>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Tjedno</th>
                    <th>Dnevni fond</th>
                    <th>Dani</th>
                    <th>Očekivano</th>
                    <th>Fond mjeseca</th>
                    <th>Realizirano</th>
                    <th>Evid.</th>
                    <th>Odstupanje</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr @class(['table-warning' => $row['over'] || $row['flagged']])>
                        <td>
                            <div class="fw-semibold">{{ $row['name'] }}</div>
                            @if($row['flagged'])
                                <span class="badge text-bg-warning">iznimka fonda</span>
                            @endif
                        </td>
                        <td>{{ $row['weekly_hours'] }} h</td>
                        <td>{{ number_format($row['daily_minutes'] / 60, 1) }} h</td>
                        <td>{{ $row['days_elapsed'] }} / {{ $row['days_month'] }}</td>
                        <td>{{ number_format($row['expected_to_date'] / 60, 1) }} h</td>
                        <td>{{ number_format($row['expected_month'] / 60, 1) }} h</td>
                        <td>{{ number_format($row['total_minutes'] / 60, 1) }} h</td>
                        <td>{{ number_format($row['evidential_minutes'] / 60, 1) }} h</td>
                        <td class="{{ $row['over'] ? 'text-danger fw-semibold' : '' }}">
                            {{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'] / 60, 1) }} h
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted">Nema osoba za prijavu u ovom opsegu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-3 mb-0">Rukovodeće osobe s ugovorenom samostalnošću (čl. 21.) ne dobivaju iznimku fonda, ali ostaju u pregledu.</p>
@endsection
