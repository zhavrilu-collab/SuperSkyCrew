@extends('layouts.organization')

@section('title', 'Kadar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Kadar</h1>
        <p class="text-muted mb-0">Evidencija osoba prema Pravilniku NN 55/2024.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex">
            <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">Svi statusi</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected($statusFilter === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('organization.people.book', $organization->slug) }}" class="btn btn-outline-secondary">Matična knjiga</a>
        <a href="{{ route('organization.people.turnover', $organization->slug) }}" class="btn btn-outline-secondary">Fluktuacija</a>
        <a href="{{ route('organization.people.payroll', $organization->slug) }}" class="btn btn-outline-secondary">Place</a>
        <a href="{{ route('organization.people.export', $organization->slug) }}" class="btn btn-outline-secondary">Izvoz CSV</a>
        <a href="{{ route('organization.structure.index', $organization->slug) }}" class="btn btn-outline-secondary">Struktura</a>
        <a href="{{ route('organization.people.create', $organization->slug) }}" class="btn btn-primary">Nova osoba</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Ime</th>
                    <th>Status</th>
                    <th>Radno mjesto</th>
                    <th>Odjel</th>
                    <th>MT</th>
                    <th>Početak</th>
                    <th>Prijava</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $person->fullName() }}</div>
                            <div class="text-muted small">{{ $person->oib ? 'OIB '.$person->oib : 'Bez OIB-a' }}</div>
                        </td>
                        <td>
                            {{ $person->status->label() }}
                            @if(($expiryCounts[$person->id] ?? 0) > 0)
                                <span class="badge text-bg-warning">istek {{ $expiryCounts[$person->id] }}</span>
                            @endif
                        </td>
                        <td>{{ $person->jobPosition?->name ?: ($person->job_title ?: '—') }}</td>
                        <td>{{ $person->department?->name ?: '—' }}</td>
                        <td>{{ $person->costCenter?->summary() ?: '—' }}</td>
                        <td>{{ $person->started_at?->format('d.m.Y') ?: '—' }}</td>
                        <td>{{ $person->user?->email ?: 'nije povezano' }}</td>
                        <td class="text-end">
                            <a href="{{ route('organization.people.review', [$organization->slug, $person]) }}" class="btn btn-outline-secondary btn-sm">Pregled</a>
                            <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="btn btn-outline-secondary btn-sm">Uredi</a>
                            @if($person->status === \App\Enums\PersonStatus::Candidate)
                                <form method="POST" action="{{ route('organization.people.hire', [$organization->slug, $person]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-success btn-sm" type="submit">Zaposli</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-muted">Nema unesenih osoba.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
