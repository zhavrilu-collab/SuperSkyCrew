@extends('layouts.organization')

@section('title', 'Poslovni segmenti')
@section('nav-suffix', 'Ustroj tvrtke')

@section('content')
<div class="page-heading">
    <h1>Poslovni segmenti</h1>
    <p class="text-muted mb-0">Slojevi industrije ili P&amp;L unutar grupacije. Mogu se vezati na poslovne jedinice.</p>
</div>

<div class="kartica-kontejner mb-4">
    <form method="POST" action="{{ route('organization.segments.store', $organization->slug) }}" class="row g-3">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="name">Naziv</label>
            <input class="form-control" name="name" id="name" required>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="code">Šifra</label>
            <input class="form-control" name="code" id="code">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="description">Opis</label>
            <input class="form-control" name="description" id="description">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit">Dodaj</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Segment</th>
                    <th>Šifra</th>
                    <th>Jedinice</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($segments as $segment)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $segment->name }}</div>
                            @if($segment->description)
                                <div class="text-muted small">{{ $segment->description }}</div>
                            @endif
                        </td>
                        <td>{{ $segment->code ?: '—' }}</td>
                        <td>{{ $units->where('business_segment_id', $segment->id)->count() }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.segments.destroy', [$organization->slug, $segment]) }}" class="d-inline" onsubmit="return confirm('Obrisati segment?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Nema poslovnih segmenata.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
