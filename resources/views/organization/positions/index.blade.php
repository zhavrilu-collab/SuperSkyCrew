@extends('layouts.organization')

@section('title', $nav === 'Sistematizacija' ? 'Plan radnih pozicija' : 'Radne pozicije')
@section('nav-suffix', $nav)

@section('content')
<div class="page-heading">
    <h1>{{ $nav === 'Sistematizacija' ? 'Plan radnih pozicija' : 'Radne pozicije' }}</h1>
    <p class="text-muted mb-0">Stolice u odjelu. Radno mjesto je uloga, pozicija je konkretno mjesto na koje sjeda osoba.</p>
</div>

<div class="kartica-kontejner mb-4">
    <form method="POST" action="{{ route('organization.positions.store', $organization->slug) }}" class="row g-3">
        @csrf
        <div class="col-md-5">
            <label class="form-label" for="job_position_id">Radno mjesto</label>
            <select class="form-select" name="job_position_id" id="job_position_id" required>
                <option value="">—</option>
                @foreach($jobs as $job)
                    <option value="{{ $job->id }}">{{ $job->summary() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" name="status" id="status">
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected($status === \App\Enums\OrgSeatStatus::Open)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="valid_from">Važi od</label>
            <input type="date" class="form-control" name="valid_from" id="valid_from" value="{{ now()->toDateString() }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit" @disabled($jobs->isEmpty())>Otvori stolicu</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Pozicija</th>
                    <th>Odjel</th>
                    <th>Status</th>
                    <th>Osoba</th>
                    <th>Važenje</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($seats as $seat)
                    <tr>
                        <td>{{ $seat->label() }}</td>
                        <td>{{ $seat->department?->name ?: $seat->jobPosition?->department?->name ?: '—' }}</td>
                        <td>{{ $seat->status->label() }}</td>
                        <td>{{ $seat->person?->fullName() ?: '—' }}</td>
                        <td class="small">{{ $seat->valid_from?->format('d.m.Y.') ?: '—' }} – {{ $seat->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.positions.update', [$organization->slug, $seat]) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="{{ \App\Enums\OrgSeatStatus::Hiring->value }}">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">Zapošljavanje</button>
                            </form>
                            @if($seat->person_id)
                                <form method="POST" action="{{ route('organization.positions.update', [$organization->slug, $seat]) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="release" value="1">
                                    <button class="btn btn-outline-secondary btn-sm" type="submit">Skini</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('organization.positions.destroy', [$organization->slug, $seat]) }}" class="d-inline" onsubmit="return confirm('Ukloniti stolicu?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Nema radnih pozicija. Otvorite stolicu iz kataloga radnih mjesta.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
