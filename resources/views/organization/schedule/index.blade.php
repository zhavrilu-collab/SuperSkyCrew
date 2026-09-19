@extends('layouts.organization')

@section('title', 'Raspored')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Raspored i smjene</h1>
        <p class="text-muted mb-0">Tjedni plan. Smjene i kalendari uređuju se u Postavkama. Kašnjenje (grace na lokaciji, zadano {{ \App\Services\ShiftResolver::LATE_GRACE_MINUTES }} min) ide u iznimke.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', [$organization->slug, 'from' => $prev->toDateString()]) }}">←</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', $organization->slug) }}">Ovaj tjedan</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', [$organization->slug, 'from' => $next->toDateString()]) }}">→</a>
        @if($canManage)
            <a class="btn btn-outline-primary" href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'vrijeme', 'section' => 'smjene']) }}">Postavke smjena</a>
        @endif
        @if($canSend)
            <form method="POST" action="{{ route('organization.schedule.plan.transfer', $organization->slug) }}" class="d-inline">
                @csrf
                <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                <button class="btn btn-outline-primary" type="submit">Prenesi tjedan u šihtericu</button>
            </form>
            <form method="POST" action="{{ route('organization.schedule.plan.send', $organization->slug) }}" class="d-inline">
                @csrf
                <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                <button class="btn btn-primary" type="submit">Pošalji plan e-mailom</button>
            </form>
        @endif
    </div>
</div>

<div class="kartica-kontejner">
    <h2 class="h6">Plan tjedna {{ $from->format('d.m.') }} – {{ $to->format('d.m.Y.') }}</h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Osoba</th>
                    @foreach($days as $day)
                        <th class="text-center {{ $day->isSunday() ? 'nedjelja' : '' }}">{{ $weekdays[$day->isoWeekday()] }}<div class="small fw-normal">{{ $day->format('d.m.') }}</div></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>{{ $person->fullName() }}{{ $person->department ? ' · '.$person->department->name : '' }}</td>
                        @foreach($days as $day)
                            @php($shift = $plan[$person->id][$day->toDateString()] ?? null)
                            <td class="text-center small">{{ $shift ? $shift->shortCode() : '·' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 1 }}" class="text-muted">Nema osoba za prikaz.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="small text-muted mb-0 mt-2">Prazne ćelije na šihterici i dalje pokazuju kod smjene. Prijenos upisuje evidencijske sate (RD) za dane bez prijave; punchovi, odsutnosti i ručne evidencije ostaju. Blagdani RH su neredovni osim ako radnik ima vlastito pravilo.</p>
</div>

@if($shiftBoard ?? false)
<div class="kartica-kontejner mt-3">
    <h2 class="h6">Otvorene smjene</h2>
    <p class="small text-muted">Preuzimanje upisuje iznimku kalendara za taj dan. Zamjena smjene ide kao zahtjev.</p>
    <div class="table-responsive mb-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>Dan</th><th>Smjena</th><th>Odjel</th><th>Mjesta</th><th></th></tr></thead>
            <tbody>
                @forelse($openShifts as $open)
                    <tr>
                        <td>{{ $open->work_date->format('d.m.Y.') }}</td>
                        <td>{{ $open->shift?->label() }}</td>
                        <td>{{ $open->department?->name ?: 'svi' }}</td>
                        <td>{{ $open->remainingSlots() }} / {{ $open->slots }}</td>
                        <td class="text-end">
                            @if($ownPerson && $open->remainingSlots() > 0)
                                <form method="POST" action="{{ route('organization.schedule.open.claim', [$organization->slug, $open]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit">Preuzmi</button>
                                </form>
                            @endif
                            @if($canManage)
                                <form method="POST" action="{{ route('organization.schedule.open.destroy', [$organization->slug, $open]) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Ukloni</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema otvorenih smjena.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($canManage)
        <form method="POST" action="{{ route('organization.schedule.open.store', $organization->slug) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Dan</label>
                <input type="date" class="form-control" name="work_date" value="{{ $from->toDateString() }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Smjena</label>
                <select class="form-select" name="shift_id" required>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Odjel</label>
                <select class="form-select" name="department_id">
                    <option value="">svi</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Mjesta</label>
                <input type="number" min="1" max="20" class="form-control" name="slots" value="1">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" type="submit">Objavi</button>
            </div>
        </form>
    @endif
</div>
@endif
@endsection
