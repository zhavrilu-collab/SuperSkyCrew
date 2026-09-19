@extends('layouts.organization')

@section('title', 'Grant sati')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="page-heading">
    <h1>Grant / projektni sati</h1>
    <p class="text-muted mb-0">Četvrti sloj evidencije. Ne ulazi u zakonski slog, šihtericu ni sati za plaće.</p>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="kartica-kontejner mb-3">
    <h2 class="h6">Projekti</h2>
    <div class="table-responsive mb-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>Šifra</th><th>Naziv</th></tr></thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td>{{ $project->code }}</td>
                        <td>{{ $project->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted">Još nema projekata.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.grants.projects.store', $organization->slug) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3"><label class="form-label">Šifra</label><input class="form-control" name="code" required maxlength="32"></div>
        <div class="col-md-6"><label class="form-label">Naziv</label><input class="form-control" name="name" required maxlength="160"></div>
        <div class="col-md-3"><button class="btn btn-primary" type="submit">Dodaj projekt</button></div>
    </form>
</div>

<div class="kartica-kontejner">
    <h2 class="h6">Upis sati</h2>
    <form method="GET" action="{{ route('organization.grants.index', $organization->slug) }}" class="row g-2 align-items-end mb-3">
        <div class="col-auto"><label class="form-label">Od</label><input type="date" class="form-control" name="from" value="{{ $from }}"></div>
        <div class="col-auto"><label class="form-label">Do</label><input type="date" class="form-control" name="to" value="{{ $to }}"></div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Filtriraj</button></div>
    </form>
    <form method="POST" action="{{ route('organization.grants.store', $organization->slug) }}" class="row g-2 align-items-end mb-4">
        @csrf
        <div class="col-md-3">
            <label class="form-label">Osoba</label>
            <select class="form-select" name="person_id" required>
                @foreach($people as $person)
                    <option value="{{ $person->id }}">{{ $person->fullName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Projekt</label>
            <select class="form-select" name="grant_project_id" required>
                @foreach($projects->where('is_active', true) as $project)
                    <option value="{{ $project->id }}">{{ $project->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">Dan</label><input type="date" class="form-control" name="work_date" value="{{ now()->toDateString() }}" required></div>
        <div class="col-md-2"><label class="form-label">Minute</label><input type="number" min="15" max="720" step="15" class="form-control" name="minutes" value="60" required></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Upisi</button></div>
        <div class="col-12"><input class="form-control" name="note" placeholder="Napomena (nije obavezna)" maxlength="255"></div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Dan</th><th>Osoba</th><th>Projekt</th><th>Sati</th><th></th></tr></thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->work_date->format('d.m.Y.') }}</td>
                        <td>{{ $entry->person?->fullName() }}</td>
                        <td>{{ $entry->project?->label() }}</td>
                        <td>{{ $entry->hoursLabel() }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.grants.destroy', [$organization->slug, $entry]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Obriši</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema grant sati u rasponu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
