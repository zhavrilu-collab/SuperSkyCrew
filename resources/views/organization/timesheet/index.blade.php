@extends('layouts.organization')

@section('title', 'Šihterica')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Šihterica</h1>
        <p class="text-muted mb-0">{{ $presentIds->count() }} trenutno na poslu</p>
    </div>
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="date" class="form-control" name="from" value="{{ $from->toDateString() }}">
        <input type="date" class="form-control" name="to" value="{{ $to->toDateString() }}">
        <select class="form-select" name="odjel" style="min-width: 10rem">
            <option value="">Svi odjeli</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((int) $departmentId === (int) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <select class="form-select" name="lokacija" style="min-width: 10rem">
            <option value="">Sve lokacije</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected((int) $locationId === (int) $location->id)>{{ $location->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary">Prikaži</button>
        <a class="btn btn-outline-primary" href="{{ route('organization.exceptions.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Iznimke</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.fund', [$organization->slug, 'mjesec' => $from->format('Y-m')]) }}">Fond</a>
        @if($canInspect)
            <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.inspection', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Inspekcija</a>
        @endif
        @if($canPayroll)
            <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.payroll-hours', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Sati za plaće</a>
            <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.export', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Izvoz CSV</a>
            <a class="btn btn-outline-primary" href="{{ route('organization.people.payroll', $organization->slug) }}">Podaci za plaće</a>
        @endif
    </form>
</div>

@if($periodLock)
    <div class="alert alert-warning">
        Razdoblje {{ $periodLock->label() }} je zaključano
        @if($periodLock->locked_at)
            {{ $periodLock->locked_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}
        @endif
        @if($periodLock->lockedBy)
            · {{ $periodLock->lockedBy->name }}
        @else
            · automatika
        @endif
        . Punchovi i odsutnosti se ne mogu mijenjati.
    </div>
@elseif($canLock || $canManual)
    <div class="kartica-kontejner mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                @if($canLock)
                    <div class="fw-semibold">Zaključaj {{ sprintf('%02d/%d', $from->month, $from->year) }}</div>
                    <div class="small text-muted">
                        Nakon zaključavanja slogovi NN 55/2024 više se ne smiju mijenjati.
                        @if($exceptionCount > 0)
                            Otvoreno je još <a href="{{ route('organization.exceptions.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">{{ $exceptionCount }} iznimki</a> u prikazu.
                        @endif
                    </div>
                @elseif($canManual)
                    <div class="fw-semibold">Plan → šihterica</div>
                    <div class="small text-muted">Evidencijski sati iz smjene za dane bez prijave, u prikazanom rasponu.</div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($canManual)
                    <form method="POST" action="{{ route('organization.timesheet.plan', $organization->slug) }}">
                        @csrf
                        <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                        <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                        @if($departmentId)
                            <input type="hidden" name="odjel" value="{{ $departmentId }}">
                        @endif
                        @if($locationId)
                            <input type="hidden" name="lokacija" value="{{ $locationId }}">
                        @endif
                        <button class="btn btn-outline-primary" type="submit">Prenesi plan</button>
                    </form>
                @endif
                @if($canLock)
                    <form method="POST" action="{{ route('organization.timesheet.lock', $organization->slug) }}">
                        @csrf
                        <input type="hidden" name="year" value="{{ $from->year }}">
                        <input type="hidden" name="month" value="{{ $from->month }}">
                        <button class="btn btn-dark" type="submit">Zaključaj razdoblje</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endif

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    @foreach($days as $day)
                        <th class="text-center {{ $day->isSunday() ? 'nedjelja' : '' }}">{{ $day->format('d.m.') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>
                            {{ $person->fullName() }}
                            @if($presentIds->contains($person->id))
                                <span class="badge text-bg-success">na poslu</span>
                            @endif
                        </td>
                        @foreach($days as $day)
                            @php($entry = optional($entries->get($person->id))->first(fn ($item) => $item->work_date->toDateString() === $day->toDateString()))
                            @php($shift = $plan[$person->id][$day->toDateString()] ?? null)
                            <td class="text-center">
                                <a href="{{ route('organization.timesheet.day', [$organization->slug, $person, $day->toDateString()]) }}" class="{{ $entry ? '' : 'text-muted text-decoration-none' }}">
                                    {{ $entry && $entry->absence_code ? $entry->absence_code : ($entry ? number_format(($entry->evidential_minutes ?: $entry->total_minutes) / 60, 1) : ($shift ? $shift->shortCode() : '·')) }}{{ $entry && $entry->evidential_manual ? '*' : '' }}{{ $entry && $entry->exception_code ? ' !' : '' }}
                                </a>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 1 + count($days) }}" class="text-muted">Nema osoba za odabrani filtar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($absenceCodes->isNotEmpty())
    <div class="kartica-kontejner mt-3">
        <h2 class="h6 mb-2">Značenje kratica (čl. 18. st. 2.)</h2>
        <div class="d-flex flex-wrap gap-2">
            @foreach($absenceCodes as $code)
                <span class="badge {{ $code->badgeClass() }}" title="{{ $code->legendLine() }}">{{ $code->code }} · {{ $code->name }}</span>
            @endforeach
        </div>
        <p class="small text-muted mb-0 mt-2">Puno značenje vidi se u Postavkama → Vrijeme → Šifrarnik sati i na inspekcijskom ispisu.</p>
    </div>
@endif
@endsection
