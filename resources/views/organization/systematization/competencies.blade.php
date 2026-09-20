@extends('layouts.organization')

@section('title', 'Kompetencije')
@section('nav-suffix', 'Sistematizacija')

@section('content')
<div class="page-heading">
    <h1>Kompetencije</h1>
    <p class="text-muted mb-0">Šifrarnik vještina i matrica prema radnom mjestu. Kvalifikacije na kartici osobe ostaju evidencija certifikata.</p>
</div>

<div class="kartica-kontejner mb-4">
    <form method="POST" action="{{ route('organization.competencies.store', $organization->slug) }}" class="row g-3">
        @csrf
        <div class="col-md-5">
            <label class="form-label" for="name">Naziv</label>
            <input class="form-control" name="name" id="name" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="kind">Vrsta</label>
            <select class="form-select" name="kind" id="kind">
                @foreach($kinds as $kind)
                    <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit">Dodaj u šifrarnik</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner mb-4">
    <h2 class="h6">Veza na radno mjesto</h2>
    <form method="POST" action="{{ route('organization.competencies.attach', $organization->slug) }}" class="row g-3">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="job_position_id">Radno mjesto</label>
            <select class="form-select" name="job_position_id" id="job_position_id" required>
                <option value="">—</option>
                @foreach($jobs as $job)
                    <option value="{{ $job->id }}">{{ $job->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="competency_id">Kompetencija</label>
            <select class="form-select" name="competency_id" id="competency_id" required>
                <option value="">—</option>
                @foreach($competencies as $competency)
                    <option value="{{ $competency->id }}">{{ $competency->name }} · {{ $competency->kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="required_level">Razina 1–5</label>
            <input type="number" min="1" max="5" class="form-control" name="required_level" id="required_level" value="3" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-primary w-100" type="submit" @disabled($competencies->isEmpty() || $jobs->isEmpty())>Poveži</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Radno mjesto</th>
                    <th>Matrica</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td class="fw-semibold">{{ $job->name }}</td>
                        <td>
                            @forelse($job->competencies as $competency)
                                <form method="POST" action="{{ route('organization.competencies.detach', $organization->slug) }}" class="d-inline me-2">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="job_position_id" value="{{ $job->id }}">
                                    <input type="hidden" name="competency_id" value="{{ $competency->id }}">
                                    <span class="small">{{ $competency->name }} ({{ $competency->pivot->required_level }})</span>
                                    <button class="btn btn-link btn-sm p-0" type="submit">×</button>
                                </form>
                            @empty
                                <span class="text-muted">nema veza</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted">Nema radnih mjesta.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
