@extends('layouts.organization')

@section('title', 'Struktura')

@section('content')
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="h5 mb-1">Struktura organizacije</h2>
        <p class="text-muted mb-0">Odjeli, radna mjesta i mjesta troška s datumom važenja. Pregled stanja na dan.</p>
    </div>
    <form method="GET" action="{{ route('organization.settings.index', $organization->slug) }}" class="d-flex gap-2 align-items-end">
        <input type="hidden" name="tab" value="organizacija">
        <input type="hidden" name="section" value="ustroj">
        <div>
            <label class="form-label mb-1" for="na">Stanje na dan</label>
            <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
        </div>
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
    </form>
</div>


<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="kartica-kontejner h-100">
            <div class="card-body">
                <h2 class="h6">Odjeli na {{ $on->format('d.m.Y.') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Odjel</th>
                                <th>Voditelj</th>
                                <th>Važenje</th>
                                <th>Osobe</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $department)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $department->name }}</div>
                                        <div class="text-muted small">{{ $department->code ?: 'bez šifre' }}</div>
                                    </td>
                                    <td>{{ $department->manager?->name ?: '—' }}</td>
                                    <td class="small">
                                        {{ $department->valid_from?->format('d.m.Y.') ?: '—' }}
                                        –
                                        {{ $department->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}
                                    </td>
                                    <td>{{ $people->where('department_id', $department->id)->count() }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('organization.structure.departments.destroy', [$organization->slug, $department]) }}" class="d-inline" onsubmit="return confirm('Obrisati odjel?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">Nema odjela važećih na taj dan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="kartica-kontejner h-100">
            <div class="card-body">
                <h2 class="h6">Radna mjesta na {{ $on->format('d.m.Y.') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Naziv</th>
                                <th>RAD1G</th>
                                <th>GO</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($positions as $position)
                                <tr>
                                    <td>{{ $position->name }}</td>
                                    <td>{{ $position->rad1gLabel() ?: '—' }}</td>
                                    <td>{{ $position->annual_leave_days ?? '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('organization.structure.positions.destroy', [$organization->slug, $position]) }}" class="d-inline" onsubmit="return confirm('Obrisati radno mjesto?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">Nema radnih mjesta važećih na taj dan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kartica-kontejner mb-4">
    <div class="card-body">
        <h2 class="h6">Osobe prema odjelu (trenutna dodjela)</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Osoba</th>
                        <th>Odjel</th>
                        <th>Radno mjesto</th>
                        <th>Mjesto troška</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($people as $person)
                        <tr>
                            <td>{{ $person->fullName() }}</td>
                            <td>{{ $person->department?->name ?: '—' }}</td>
                            <td>{{ $person->jobPosition?->summary() ?: ($person->job_title ?: '—') }}</td>
                            <td>{{ $person->costCenter?->summary() ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted">Nema aktivnih osoba.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('organization.structure.departments.store', $organization->slug) }}" class="kartica-kontejner">
            @csrf
            <div class="card-body">
                <h2 class="h6">Novi odjel</h2>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="dept_name">Naziv</label>
                        <input class="form-control" name="name" id="dept_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_code">Šifra</label>
                        <input class="form-control" name="code" id="dept_code">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="dept_manager">Voditelj odjela</label>
                        <select class="form-select" name="manager_user_id" id="dept_manager">
                            <option value="">—</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="dept_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="dept_to">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-primary" type="submit">Spremi odjel</button>
            </div>
        </form>
    </div>
    <div class="col-lg-6">
        <form method="POST" action="{{ route('organization.structure.positions.store', $organization->slug) }}" class="kartica-kontejner">
            @csrf
            <div class="card-body">
                <h2 class="h6">Novo radno mjesto</h2>
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label" for="pos_name">Naziv</label>
                        <input class="form-control" name="name" id="pos_name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="pos_rad1g_q">RAD1G (NKZ-10)</label>
                        @include('partials.nkz-rad1g-select')
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pos_go">Fond GO</label>
                        <input type="number" min="0" max="50" class="form-control" name="annual_leave_days" id="pos_go">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pos_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="pos_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pos_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="pos_to">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-primary" type="submit">Spremi radno mjesto</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mt-1 mb-4">
    <div class="col-lg-7">
        <div class="kartica-kontejner h-100">
            <div class="card-body">
                <h2 class="h6">Mjesta troška na {{ $on->format('d.m.Y.') }}</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Šifra</th>
                                <th>Naziv</th>
                                <th>Važenje</th>
                                <th>Osobe</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costCenters as $costCenter)
                                <tr>
                                    <td>{{ $costCenter->code }}</td>
                                    <td>{{ $costCenter->name }}</td>
                                    <td class="small">
                                        {{ $costCenter->valid_from?->format('d.m.Y.') ?: '—' }}
                                        –
                                        {{ $costCenter->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}
                                    </td>
                                    <td>{{ $people->where('cost_center_id', $costCenter->id)->count() }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('organization.structure.cost-centers.destroy', [$organization->slug, $costCenter]) }}" class="d-inline" onsubmit="return confirm('Obrisati mjesto troška?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">Nema mjesta troška važećih na taj dan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="POST" action="{{ route('organization.structure.cost-centers.store', $organization->slug) }}" class="kartica-kontejner h-100">
            @csrf
            <div class="card-body">
                <h2 class="h6">Novo mjesto troška</h2>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label" for="mt_code">Šifra</label>
                        <input class="form-control" name="code" id="mt_code" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="mt_name">Naziv</label>
                        <input class="form-control" name="name" id="mt_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="mt_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="mt_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="mt_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="mt_to">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-primary" type="submit">Spremi mjesto troška</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="nkz-rad1g-podaci">@json($nkzRad1gOptions ?? [])</script>
<script src="{{ asset('js/nkz-select.js') }}"></script>
@endpush
