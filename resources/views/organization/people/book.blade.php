@extends('layouts.print')

@section('title', 'Matična knjiga — '.$organization->name)
@section('document-class', 'ispis-dokument--wide')

@section('toolbar')
    <a href="{{ route('organization.people.index', $organization->slug) }}" class="small">← Dosjei zaposlenika</a>
    <form method="GET" class="d-flex gap-2">
        <label class="form-label mb-0 align-self-center" for="na">Stanje na dan</label>
        <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
    </form>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('organization.people.export', [$organization->slug, 'na' => $on->toDateString()]) }}">Izvoz CSV</a>
        <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
    </div>
@endsection

@section('content')
    <h1 class="h4 mb-1">Matična knjiga radnika</h1>
    <p class="text-muted">Stanje na {{ $on->format('d.m.Y.') }} · {{ $people->count() }} aktivnih. Odjel i radno mjesto čitaju se iz povijesti angažmana.</p>

    <div class="table-responsive table-responsive-no-sticky">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>Rbr</th>
                    <th>Prezime i ime</th>
                    <th>OIB</th>
                    <th>Spol</th>
                    <th>Rođenje</th>
                    <th>Državljanstvo</th>
                    <th>Status</th>
                    <th>Radno mjesto</th>
                    <th>Odjel</th>
                    <th>Ugovor</th>
                    <th>Početak</th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $index => $person)
                    @php($assignment = $person->assignmentOn($on))
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $person->last_name }} {{ $person->first_name }}</td>
                        <td>{{ $person->oib ?: '—' }}</td>
                        <td>{{ $person->genderLabel() }}</td>
                        <td>{{ $person->date_of_birth?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $person->citizenship ?: '—' }}</td>
                        <td>{{ $assignment?->status->label() ?: $person->engagementLabel() }}</td>
                        <td>{{ $assignment?->jobLabel() ?: $person->jobLabel() }}</td>
                        <td>{{ $assignment?->department?->name ?: ($person->department?->name ?: '—') }}</td>
                        <td>{{ $person->instrument_title ?: ($person->contract_type?->label() ?: '—') }}</td>
                        <td>{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-muted">Nema aktivnih osoba na taj dan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3 mb-0">Ispisao {{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}. Nije preslika osobne iskaznice.</p>
@endsection
