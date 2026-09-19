@extends('layouts.organization')

@section('title', 'Kadrovi')
@section('nav-suffix', 'Kadrovi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Kadrovi</h1>
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
        <a href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'organizacija', 'section' => 'ustroj']) }}" class="btn btn-outline-secondary">Struktura</a>
        <a href="{{ route('organization.people.create', $organization->slug) }}" class="btn btn-primary">Nova osoba</a>
    </div>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
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
                    <th class="table-akcije"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td class="tablica-osoba-ime">
                            <a href="{{ route('organization.people.edit', [$organization->slug, $person, 'tab' => $person->status === \App\Enums\PersonStatus::Candidate ? 'odabir' : 'pregled']) }}">{{ $person->fullName() }}</a>
                            <div class="text-muted small">{{ $person->oib ? 'OIB '.$person->oib : 'Bez OIB-a' }}</div>
                            @if($person->status === \App\Enums\PersonStatus::Candidate)
                                <div class="small">{{ $person->hasCv() ? 'CV priložen' : 'nema CV' }} · razgovori {{ $person->interview_notes_count }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $person->engagementLabel() }}
                            @if(($expiryCounts[$person->id] ?? 0) > 0)
                                <span class="badge text-bg-warning">istek {{ $expiryCounts[$person->id] }}</span>
                            @endif
                        </td>
                        <td>{{ $person->jobPosition?->name ?: ($person->job_title ?: '—') }}</td>
                        <td>{{ $person->department?->name ?: '—' }}</td>
                        <td>{{ $person->costCenter?->summary() ?: '—' }}</td>
                        <td>{{ $person->started_at?->format('d.m.Y') ?: '—' }}</td>
                        <td>{{ $person->user?->email ?: 'nije povezano' }}</td>
                        <td class="text-end table-akcije">
                            <a href="{{ route('organization.people.review', [$organization->slug, $person]) }}" class="btn btn-outline-secondary btn-sm">Pregled</a>
                            <a href="{{ route('organization.people.edit', [$organization->slug, $person, 'tab' => $person->status === \App\Enums\PersonStatus::Candidate ? 'odabir' : 'pregled']) }}" class="btn btn-outline-secondary btn-sm">Uredi</a>
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
