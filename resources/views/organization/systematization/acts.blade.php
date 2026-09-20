@extends('layouts.organization')

@section('title', 'Interni akti')
@section('nav-suffix', 'Sistematizacija')

@section('content')
<div class="page-heading">
    <h1>Interni akti</h1>
    <p class="text-muted mb-0">Pravilnici i kodeksi organizacije. Nije isto što dosje radnika.</p>
</div>

<div class="kartica-kontejner mb-4">
    <form method="POST" action="{{ route('organization.acts.store', $organization->slug) }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="title">Naziv</label>
            <input class="form-control" name="title" id="title" required>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="kind">Vrsta</label>
            <select class="form-select" name="kind" id="kind">
                @foreach($kinds as $kind)
                    <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="version">Verzija</label>
            <input class="form-control" name="version" id="version" placeholder="1.0">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="published_at">Objavljeno</label>
            <input type="date" class="form-control" name="published_at" id="published_at" value="{{ now()->toDateString() }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="file">Datoteka</label>
            <input type="file" class="form-control" name="file" id="file">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="must_read" id="must_read" value="1">
                <label class="form-check-label" for="must_read">Mora pročitati</label>
            </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary" type="submit">Spremi akt</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Akt</th>
                    <th>Vrsta</th>
                    <th>Verzija</th>
                    <th>Objavljeno</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($acts as $act)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $act->title }}</div>
                            @if($act->must_read)
                                <div class="text-muted small">obavezno pročitati</div>
                            @endif
                        </td>
                        <td>{{ $act->kind->label() }}</td>
                        <td>{{ $act->version ?: '—' }}</td>
                        <td>{{ $act->published_at?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            @if($act->hasFile())
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('organization.acts.download', [$organization->slug, $act]) }}">Preuzmi</a>
                            @endif
                            <form method="POST" action="{{ route('organization.acts.destroy', [$organization->slug, $act]) }}" class="d-inline" onsubmit="return confirm('Obrisati akt?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema internih akata.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
