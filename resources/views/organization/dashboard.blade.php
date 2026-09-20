@extends('layouts.organization')

@section('title', 'Nadzorna ploča')
@section('nav-suffix', 'Nadzorna ploča')

@section('content')
<div class="page-heading">
    <h1>Nadzorna ploča</h1>
    <p class="text-muted mb-0">{{ auth()->user()->name }} · uloga: <strong>{{ $membership->role->label() }}</strong></p>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-kartica">
            <div class="kpi-broj">{{ $presentCount }}</div>
            <div class="kpi-label">Na poslu</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.people.index', $organization->slug) }}" class="kpi-kartica {{ $canAccessPeople ? '' : 'pe-none' }}">
            <div class="kpi-broj">{{ $peopleCount }}</div>
            <div class="kpi-label">Kadrovi</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.clock', $organization->slug) }}" class="kpi-kartica">
            <div class="kpi-broj">{{ $hasOwnPerson ? 'Sat' : '—' }}</div>
            <div class="kpi-label">Prijava / odjava</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.requests.index', $organization->slug) }}" class="kpi-kartica">
            <div class="kpi-broj">{{ $leave['remaining'] ?? '—' }}</div>
            <div class="kpi-label">GO preostalo</div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.absences.calendar', $organization->slug) }}" class="kpi-kartica">
            <div class="kpi-broj">{{ $absentToday }}</div>
            <div class="kpi-label">Odsutni danas</div>
        </a>
    </div>
    @if($canApprove)
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.approvals.index', $organization->slug) }}" class="kpi-kartica">
            <div class="kpi-broj">{{ $pendingApprovals }}</div>
            <div class="kpi-label">Odobrenja</div>
        </a>
    </div>
    @endif
    @if($canAccessPeople)
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.expiries.index', $organization->slug) }}" class="kpi-kartica">
            <div class="kpi-broj">{{ $expiryCount }}</div>
            <div class="kpi-label">Isteci (30 d.)</div>
        </a>
    </div>
    @endif
    @if($canAccessTime || $canExportPayroll)
    <div class="col-6 col-md-3">
        <a href="{{ route('organization.exceptions.index', $organization->slug) }}" class="kpi-kartica">
            <div class="kpi-broj">{{ $exceptionCount }}</div>
            <div class="kpi-label">Iznimke (7 d.)</div>
        </a>
    </div>
    @endif
</div>

@if(($canAccessTime || $canExportPayroll) && $presentPeople->isNotEmpty())
<div class="kartica-kontejner mb-3">
    <div class="fw-semibold text-tema mb-2">Trenutno na poslu</div>
    <ul class="list-unstyled mb-0">
        @foreach($presentPeople as $row)
            <li class="d-flex justify-content-between gap-2 py-1 border-bottom">
                <span>{{ $row['person']->fullName() }}</span>
                <span class="text-muted small">{{ $row['punch']->type->label() }} · {{ $row['punch']->occurred_at_device->timezone(config('app.timezone'))->format('H:i') }}</span>
            </li>
        @endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    @if($canAccessTime || $canExportPayroll)
    <div class="col-md-4">
        <a href="{{ route('organization.timesheet.index', $organization->slug) }}" class="kartica-kontejner d-block text-decoration-none">
            <div class="fw-semibold text-tema">Šihterica</div>
            <div class="small text-muted">{{ $canAccessTime || $canExportPayroll ? 'Otvori evidenciju radnog vremena' : 'Nema pristupa' }}</div>
        </a>
    </div>
    @endif
    @if($ownPerson)
    <div class="col-md-4">
        <a href="{{ route('organization.people.review', [$organization->slug, $ownPerson]) }}" class="kartica-kontejner d-block text-decoration-none">
            <div class="fw-semibold text-tema">Moj pregled</div>
            <div class="small text-muted">Čl. 4. / čl. 5.</div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('organization.timesheet.mine', $organization->slug) }}" class="kartica-kontejner d-block text-decoration-none">
            <div class="fw-semibold text-tema">Moj tjedan</div>
            <div class="small text-muted">Uvid u evidenciju RV</div>
        </a>
    </div>
    @endif
    @if($kioskLocation && $canAccessTime)
    <div class="col-md-4">
        <a href="{{ route('organization.kiosk', [$organization->slug, $kioskLocation->kiosk_token]) }}" class="kartica-kontejner d-block text-decoration-none" target="_blank" rel="noopener">
            <div class="fw-semibold text-tema">Kiosk</div>
            <div class="small text-muted">{{ $kioskLocation->name }}</div>
        </a>
    </div>
    @endif
</div>
@endsection
