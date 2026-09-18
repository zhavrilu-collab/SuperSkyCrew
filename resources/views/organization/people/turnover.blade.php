@extends('layouts.organization')

@section('title', 'Fluktuacija')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Fluktuacija</h1>
        <p class="text-muted mb-0">Ulazci i izlasci u razdoblju. Stopa = izlasci / broj aktivnih na početku razdoblja.</p>
    </div>
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="date" class="form-control" name="from" value="{{ $from->toDateString() }}">
        <input type="date" class="form-control" name="to" value="{{ $to->toDateString() }}">
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
        <a class="btn btn-outline-primary" href="{{ route('organization.people.book', $organization->slug) }}">Matična knjiga</a>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Aktivni na početku</div>
                <div class="h3 mb-0">{{ $startCount }}</div>
                <div class="small text-muted">{{ $from->format('d.m.Y.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Ulazci</div>
                <div class="h3 mb-0">{{ $hires->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Izlazci</div>
                <div class="h3 mb-0">{{ $exits->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Aktivni na kraju · stopa</div>
                <div class="h3 mb-0">{{ $endCount }}{{ $rate === null ? '' : ' · '.$rate.' %' }}</div>
                <div class="small text-muted">{{ $to->format('d.m.Y.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Ulazci</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Osoba</th>
                                <th>Početak</th>
                                <th>Posao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hires as $person)
                                <tr>
                                    <td>{{ $person->fullName() }}</td>
                                    <td>{{ $person->started_at?->format('d.m.Y.') }}</td>
                                    <td>{{ $person->jobLabel() }}{{ $person->department ? ' · '.$person->department->name : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Nema ulazaka u razdoblju.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Izlazci</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Osoba</th>
                                <th>Prestanak</th>
                                <th>Razlog</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exits as $person)
                                <tr>
                                    <td>{{ $person->fullName() }}</td>
                                    <td>{{ $person->ended_at?->format('d.m.Y.') }}</td>
                                    <td>{{ $person->ended_reason ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">Nema izlazaka u razdoblju.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
