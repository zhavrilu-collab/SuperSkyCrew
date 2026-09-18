@extends('layouts.organization')

@section('title', 'Šihterica')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Šihterica</h1>
        <p class="text-muted mb-0">{{ $presentIds->count() }} trenutno na poslu</p>
    </div>
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="date" class="form-control" name="from" value="{{ $from->toDateString() }}">
        <input type="date" class="form-control" name="to" value="{{ $to->toDateString() }}">
        <button class="btn btn-outline-secondary">Prikaži</button>
        <a class="btn btn-outline-primary" href="{{ route('organization.exceptions.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Iznimke</a>
        @if($canInspect)
            <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.inspection', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Inspekcija</a>
        @endif
        @if($canPayroll)
            <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.export', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Izvoz CSV</a>
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
        @endif
        . Punchovi i odsutnosti se ne mogu mijenjati.
    </div>
@elseif($canLock)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="fw-semibold">Zaključaj {{ sprintf('%02d/%d', $from->month, $from->year) }}</div>
                <div class="small text-muted">
                    Nakon zaključavanja slogovi NN 55/2024 više se ne smiju mijenjati.
                    @if($exceptionCount > 0)
                        Otvoreno je još <a href="{{ route('organization.exceptions.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">{{ $exceptionCount }} iznimki</a> u prikazu.
                    @endif
                </div>
            </div>
            <form method="POST" action="{{ route('organization.timesheet.lock', $organization->slug) }}">
                @csrf
                <input type="hidden" name="year" value="{{ $from->year }}">
                <input type="hidden" name="month" value="{{ $from->month }}">
                <button class="btn btn-dark" type="submit">Zaključaj razdoblje</button>
            </form>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    @foreach($days as $day)
                        <th class="text-center {{ $day->isSunday() ? 'text-danger' : '' }}">{{ $day->format('d.m.') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($people as $person)
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
                                    {{ $entry && $entry->absence_code ? $entry->absence_code : ($entry ? number_format($entry->total_minutes / 60, 1) : ($shift ? $shift->shortCode() : '·')) }}{{ $entry && $entry->exception_code ? ' !' : '' }}
                                </a>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
