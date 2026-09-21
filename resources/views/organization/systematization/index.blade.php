@extends('layouts.organization')

@section('title', 'Opisi radnih mjesta')
@section('nav-suffix', 'Sistematizacija')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Opisi radnih mjesta</h1>
        <p class="text-muted mb-0">Katalog uloga s odgovornostima, RAD1G šifrom i brojem popunjenih mjesta na odabrani dan.</p>
    </div>
    <a href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'organizacija', 'section' => 'ustroj', 'katalog' => 'mjesta']) }}" class="btn btn-primary">Novo radno mjesto</a>
</div>

<div class="kartica-kontejner">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
        <div>
            <label class="form-label mb-1" for="na">Stanje na dan</label>
            <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
        </div>
        <div>
            <label class="form-label mb-1" for="q">Pretraga</label>
            <input class="form-control" name="q" id="q" value="{{ $q }}" placeholder="Naziv, odjel ili RAD1G">
        </div>
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
    </form>

    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Radno mjesto</th>
                    <th>Odjel</th>
                    <th>Poslovna jedinica</th>
                    <th>RAD1G</th>
                    <th>Fond GO</th>
                    <th>Platni razred</th>
                    <th>Popunjeno</th>
                    <th>Važi od</th>
                    <th>Važi do</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($positions as $position)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $position->name }}</div>
                            @if($position->duties)
                                <div class="text-muted small">Dužnosti: {{ \Illuminate\Support\Str::limit($position->duties, 80) }}</div>
                            @endif
                        </td>
                        <td>{{ $position->department?->name ?: '—' }}</td>
                        <td>{{ $position->department?->enterpriseUnit?->name ?: '—' }}</td>
                        <td>{{ $position->rad1gLabel() ?: '—' }}</td>
                        <td>{{ $position->annual_leave_days !== null ? $position->annual_leave_days : '—' }}</td>
                        <td>{{ $position->pay_grade ?: '—' }}</td>
                        <td>{{ (int) ($filled[$position->id] ?? 0) }}</td>
                        <td>{{ $position->valid_from?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $position->valid_to?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'organizacija', 'section' => 'ustroj', 'katalog' => 'mjesta', 'mjesto' => $position->id, 'na' => $on->toDateString()]) }}">Uredi</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-muted">Nema radnih mjesta na odabrani dan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
