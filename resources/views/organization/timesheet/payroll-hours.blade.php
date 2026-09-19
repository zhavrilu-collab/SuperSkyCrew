@extends('layouts.organization')

@section('title', 'Sati za plaće')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Sati za plaće</h1>
        <p class="text-muted mb-0">Evidencijske šifre × sati × mjesto troška, za vanjski obračun. Nije vlastita plaća.</p>
    </div>
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="date" class="form-control" name="from" value="{{ $from->toDateString() }}">
        <input type="date" class="form-control" name="to" value="{{ $to->toDateString() }}">
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.payroll-hours-export', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Izvoz CSV</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.timesheet.fund', [$organization->slug, 'mjesec' => $from->format('Y-m')]) }}">Mjesečni fond</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.people.payroll', $organization->slug) }}">Podaci za plaće</a>
    </form>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>OIB</th>
                    <th>Šifra</th>
                    <th>Mjesto troška</th>
                    <th class="text-end">Sati</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="font-monospace">{{ $row['oib'] ?: '—' }}</td>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['cost_center'] ?: '—' }}</td>
                        <td class="text-end">{{ number_format($row['hours'], 2) }} <span class="text-muted">{{ $row['minutes'] }} min</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted">Nema evidencijskih sati u odabranom razdoblju.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-3 mb-0">JSON: <code>{{ route('organization.payroll.hours.api', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}</code> (ista prijava, pravo izvoza za plaće).</p>
@endsection
