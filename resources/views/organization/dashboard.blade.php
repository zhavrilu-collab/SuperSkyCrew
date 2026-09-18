@extends('layouts.organization')

@section('title', 'Nadzorna ploča')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h1 class="h4 mb-1">Nadzorna ploča</h1>
        <p class="text-muted mb-0">{{ auth()->user()->name }} · uloga: <strong>{{ $membership->role->label() }}</strong></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Na poslu</div>
                <div class="h3 mb-0">{{ $presentCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.people.index', $organization->slug) }}" class="text-decoration-none {{ $canAccessPeople ? '' : 'pe-none' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Kadar</div>
                <div class="h3 mb-0 text-dark">{{ $peopleCount }}</div>
                <div class="small {{ $canAccessPeople ? 'text-primary' : 'text-muted' }}">{{ $canAccessPeople ? 'Otvori evidenciju' : 'Nema pristupa' }}</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.timesheet.index', $organization->slug) }}" class="text-decoration-none {{ $canAccessTime || $canExportPayroll ? '' : 'pe-none' }}">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Šihterica</div>
                <div class="fw-semibold text-dark">{{ $canAccessTime || $canExportPayroll ? 'Dostupno' : 'Nema pristupa' }}</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.clock', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Prijava / odjava</div>
                <div class="fw-semibold text-success">{{ $hasOwnPerson ? 'Otvori sat' : 'Potrebna kartica' }}</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.requests.index', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">GO preostalo</div>
                <div class="h3 mb-0 text-dark">{{ $leave['remaining'] ?? '—' }}</div>
                <div class="small text-muted">{{ $leave ? 'staro '.$leave['remaining_old'].' / novo '.$leave['remaining_new'] : 'Potrebna kartica' }}</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.absences.calendar', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Odsutni danas</div>
                <div class="h3 mb-0 text-dark">{{ $absentToday }}</div>
                <div class="small text-primary">Otvori kalendar</div>
            </div>
        </div>
        </a>
    </div>
    @if($canApprove)
    <div class="col-md-3">
        <a href="{{ route('organization.approvals.index', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Odobrenja</div>
                <div class="h3 mb-0 text-dark">{{ $pendingApprovals }}</div>
                <div class="small text-primary">Otvori inbox</div>
            </div>
        </div>
        </a>
    </div>
    @endif
    @if($canAccessPeople)
    <div class="col-md-3">
        <a href="{{ route('organization.expiries.index', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Isteci (30 d.)</div>
                <div class="h3 mb-0 text-dark">{{ $expiryCount }}</div>
                <div class="small {{ $expiryCount ? 'text-warning' : 'text-muted' }}">{{ $expiryCount ? 'Otvori upozorenja' : 'Nema u roku' }}</div>
            </div>
        </div>
        </a>
    </div>
    @endif
    @if($canAccessTime || $canExportPayroll)
    <div class="col-md-3">
        <a href="{{ route('organization.exceptions.index', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Iznimke (7 d.)</div>
                <div class="h3 mb-0 text-dark">{{ $exceptionCount }}</div>
                <div class="small {{ $exceptionCount ? 'text-danger' : 'text-muted' }}">{{ $exceptionCount ? 'Otvori queue' : 'Nema otvorenih' }}</div>
            </div>
        </div>
        </a>
    </div>
    @endif
    @if($ownPerson)
    <div class="col-md-3">
        <a href="{{ route('organization.people.review', [$organization->slug, $ownPerson]) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Moj pregled</div>
                <div class="fw-semibold text-dark">Čl. 4. / čl. 5.</div>
                <div class="small text-primary">Otvori pisani pregled</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.requests.create', [$organization->slug, 'type' => 'personal_data']) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Promjena podataka</div>
                <div class="fw-semibold text-dark">Čl. 5. st. 2.</div>
                <div class="small text-primary">Prijavi u 8 dana</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('organization.timesheet.mine', $organization->slug) }}" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Moj tjedan</div>
                <div class="fw-semibold text-dark">Čl. 20.</div>
                <div class="small text-primary">Uvid u evidenciju RV</div>
            </div>
        </div>
        </a>
    </div>
    @endif
    @if($kioskLocation && $canAccessTime)
    <div class="col-md-3">
        <a href="{{ route('organization.kiosk', [$organization->slug, $kioskLocation->kiosk_token]) }}" class="text-decoration-none" target="_blank" rel="noopener">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Kiosk</div>
                <div class="fw-semibold text-dark">{{ $kioskLocation->name }}</div>
                <div class="small text-primary">Otvori na tabletu</div>
            </div>
        </div>
        </a>
    </div>
    @endif
</div>
@endsection
