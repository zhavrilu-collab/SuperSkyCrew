@extends('layouts.organization')

@section('title', 'Predaje dokumenata')
@section('nav-suffix', 'Zaposlenici')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Predaja ovlaštenoj osobi</h1>
        <p class="text-muted mb-0">Evidencija preuzimanja pisanog dokumenta (čl. 5. st. 5.–6.).</p>
    </div>
</div>

<div class="kartica-kontejner mb-4">
    <div class="card-header bg-white fw-semibold">Nova predaja</div>
    <div class="card-body">
        <form method="POST" action="{{ route('organization.handovers.store', $organization->slug) }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="handed_on">Datum</label>
                <input type="date" class="form-control" name="handed_on" id="handed_on" value="{{ old('handed_on', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="recipient">Ovlaštena osoba / tijelo</label>
                <input class="form-control" name="recipient" id="recipient" value="{{ old('recipient') }}" required placeholder="npr. inspektor rada">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="purpose">Svrha</label>
                <input class="form-control" name="purpose" id="purpose" value="{{ old('purpose') }}" required placeholder="npr. nadzor">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="document_kind">Dokument</label>
                <select class="form-select" name="document_kind" id="document_kind" required>
                    @foreach($kinds as $kind)
                        <option value="{{ $kind->value }}" @selected(old('document_kind') === $kind->value)>{{ $kind->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="person_id">Osoba (ako se odnosi)</label>
                <select class="form-select" name="person_id" id="person_id">
                    <option value="">cijela organizacija</option>
                    @foreach($people as $person)
                        <option value="{{ $person->id }}" @selected((string) old('person_id') === (string) $person->id)>{{ $person->fullName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="notes">Napomena</label>
                <input class="form-control" name="notes" id="notes" value="{{ old('notes') }}">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Zabilježi predaju</button>
            </div>
        </form>
    </div>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Dokument</th>
                    <th>Osoba</th>
                    <th>Primatelj</th>
                    <th>Svrha</th>
                    <th>Zabilježio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td>{{ $handover->handed_on->format('d.m.Y.') }}</td>
                        <td>
                            {{ $handover->document_kind->label() }}
                            @if($handover->notes)
                                <div class="small text-muted">{{ $handover->notes }}</div>
                            @endif
                        </td>
                        <td>{{ $handover->person?->fullName() ?: 'Organizacija' }}</td>
                        <td>{{ $handover->recipient }}</td>
                        <td>{{ $handover->purpose }}</td>
                        <td>{{ $handover->recordedBy?->name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-muted">Još nema zabilježenih predaja.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
