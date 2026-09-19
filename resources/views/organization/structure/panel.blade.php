@php
    $ustrojUrl = fn (?string $kat = null, ?string $na = null, ?string $q = null, $mjesto = false) => route('organization.settings.index', array_filter([
        'slug' => $organization->slug,
        'tab' => 'organizacija',
        'section' => 'ustroj',
        'katalog' => $kat ?? $katalog,
        'na' => ($na ?? $on->toDateString()),
        'q' => $q,
        'mjesto' => ($kat ?? $katalog) === 'mjesta'
            ? ($mjesto === false ? $selectedPosition?->id : $mjesto)
            : null,
    ], fn ($value) => $value !== null && $value !== ''));
    $tabs = [
        'shema' => 'Shema',
        'odjeli' => 'Odjeli',
        'mjesta' => 'Radna mjesta',
        'troskovi' => 'Mjesta troška',
    ];
    $peopleCount = function ($departmentId) use ($people, $on) {
        return $people->filter(function ($person) use ($departmentId, $on) {
            if ((int) $person->department_id !== (int) $departmentId) {
                return false;
            }
            if ($person->ended_at && $person->ended_at->toDateString() < $on->toDateString()) {
                return false;
            }

            return true;
        })->count();
    };
    $qLower = mb_strtolower($q);
    $filteredDeptRows = $q === '' ? $departmentRows : array_values(array_filter($departmentRows, function ($row) use ($qLower) {
        $department = $row['department'];

        return str_contains(mb_strtolower($department->name), $qLower)
            || str_contains(mb_strtolower((string) $department->code), $qLower);
    }));
    $filteredPositions = $q === '' ? $positions : $positions->filter(fn ($position) => str_contains(mb_strtolower($position->name), $qLower)
        || str_contains(mb_strtolower((string) $position->rad1g), $qLower));
    $filteredCostCenters = $q === '' ? $costCenters : $costCenters->filter(fn ($costCenter) => str_contains(mb_strtolower($costCenter->name), $qLower)
        || str_contains(mb_strtolower($costCenter->code), $qLower));
    $forest = \App\Support\DepartmentTree::forest($departments);
    $selectedPosition = $selectedPosition ?? null;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 mb-1">Struktura organizacije</h2>
        <p class="text-muted mb-0">Shema odjela, radna mjesta i mjesta troška. Pregled stanja na dan.</p>
    </div>
    <form method="GET" action="{{ route('organization.settings.index', $organization->slug) }}" class="ustroj-toolbar">
        <input type="hidden" name="tab" value="organizacija">
        <input type="hidden" name="section" value="ustroj">
        <input type="hidden" name="katalog" value="{{ $katalog }}">
        <div>
            <label class="form-label mb-1" for="na">Stanje na dan</label>
            <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
        </div>
        @if($katalog !== 'shema')
            <div>
                <label class="form-label mb-1" for="q">Pretraga</label>
                <input class="form-control" name="q" id="q" value="{{ $q }}" placeholder="Naziv ili šifra">
            </div>
        @endif
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
    </form>
</div>

<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
    @foreach($tabs as $key => $label)
        <li class="nav-item">
            <a class="nav-link @if($katalog === $key) active @endif" href="{{ $ustrojUrl($key, $on->toDateString(), $katalog === $key ? $q : null) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

@if($katalog === 'shema')
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-odjel">Novi odjel</button>
    </div>
    <div class="org-shema">
        <ul>
            <li>
                <div class="org-kutija org-kutija-root" type="button">
                    <div class="org-kutija-kapa">{{ $organization->name }}</div>
                    <div class="org-kutija-tijelo">
                        <span>{{ $departments->count() }} odjela</span>
                        <strong>{{ $people->count() }} osoba</strong>
                    </div>
                </div>
                @if($forest->isNotEmpty())
                    <ul>
                        @foreach($forest as $department)
                            @include('organization.structure.shema-node', ['department' => $department])
                        @endforeach
                    </ul>
                @endif
            </li>
        </ul>
    </div>
@elseif($katalog === 'odjeli')
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-odjel">Novi odjel</button>
    </div>
    <div class="table-responsive table-responsive-no-sticky">
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
                @forelse($filteredDeptRows as $row)
                    @php $department = $row['department']; @endphp
                    <tr>
                        <td style="padding-left: {{ 6 + ($row['depth'] * 16) }}px">
                            <div class="fw-semibold">{{ $department->name }}</div>
                            <div class="text-muted small">{{ $department->code ?: 'bez šifre' }}</div>
                        </td>
                        <td>{{ $department->manager?->name ?: '—' }}</td>
                        <td class="small">
                            {{ $department->valid_from?->format('d.m.Y.') ?: '—' }}
                            –
                            {{ $department->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}
                        </td>
                        <td>{{ $peopleCount($department->id) }}</td>
                        <td class="text-end">
                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                data-bs-toggle="modal" data-bs-target="#modal-odjel"
                                data-ustroj-edit="odjel"
                                data-action="{{ route('organization.structure.departments.update', [$organization->slug, $department]) }}"
                                data-name="{{ $department->name }}"
                                data-code="{{ $department->code }}"
                                data-parent="{{ $department->parent_id }}"
                                data-manager="{{ $department->manager_user_id }}"
                                data-from="{{ $department->valid_from?->toDateString() }}"
                                data-to="{{ $department->valid_to?->toDateString() }}">Uredi</button>
                            <form method="POST" action="{{ route('organization.structure.departments.destroy', [$organization->slug, $department]) }}" class="d-inline" onsubmit="return confirm('Obrisati odjel?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema odjela važećih na taj dan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($katalog === 'mjesta')
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-mjesto">Novo radno mjesto</button>
    </div>
    <div class="ustroj-split">
        <div class="table-responsive table-responsive-no-sticky">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Naziv</th>
                        <th>Odjel</th>
                        <th>RAD1G</th>
                        <th>GO</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filteredPositions as $position)
                        <tr class="{{ $selectedPosition?->id === $position->id ? 'ustroj-red-aktivan' : '' }}">
                            <td>
                                <a class="fw-semibold text-decoration-none" href="{{ $ustrojUrl('mjesta', $on->toDateString(), $q, $position->id) }}">{{ $position->name }}</a>
                            </td>
                            <td>{{ $position->department?->name ?: '—' }}</td>
                            <td>{{ $position->rad1g ?: '—' }}</td>
                            <td>{{ $position->annual_leave_days ?? '—' }}</td>
                            <td class="text-end">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                    data-bs-toggle="modal" data-bs-target="#modal-mjesto"
                                    data-ustroj-edit="mjesto"
                                    data-action="{{ route('organization.structure.positions.update', [$organization->slug, $position]) }}"
                                    data-name="{{ $position->name }}"
                                    data-department="{{ $position->department_id }}"
                                    data-rad1g="{{ $position->rad1g }}"
                                    data-go="{{ $position->annual_leave_days }}"
                                    data-description="{{ $position->description }}"
                                    data-from="{{ $position->valid_from?->toDateString() }}"
                                    data-to="{{ $position->valid_to?->toDateString() }}"
                                    data-people="{{ $people->where('job_position_id', $position->id)->map(fn ($person) => $person->fullName().' · '.($person->contract_type?->label() ?: '—').' · '.($person->started_at?->format('d.m.Y.') ?: '—'))->implode("\n") }}">Uredi</button>
                                <form method="POST" action="{{ route('organization.structure.positions.destroy', [$organization->slug, $position]) }}" class="d-inline" onsubmit="return confirm('Obrisati radno mjesto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Nema radnih mjesta važećih na taj dan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="kartica-kontejner p-3 mb-0">
            @if($selectedPosition)
                <div class="forma-sekcija mt-0 pt-0 border-0">
                    <h2>{{ $selectedPosition->name }}</h2>
                    <p>{{ $selectedPosition->department?->name ?: 'Bez odjela' }}{{ $selectedPosition->rad1g ? ' · RAD1G '.$selectedPosition->rad1g : '' }}</p>
                </div>
                @if($selectedPosition->description)
                    <p class="small text-muted">{{ $selectedPosition->description }}</p>
                @endif
                <div class="forma-sekcija">
                    <h2>Osobe na ovom mjestu</h2>
                    <p>Ugovor i datumi ostaju na kartici osobe.</p>
                </div>
                @php
                    $assigned = $people->filter(function ($person) use ($selectedPosition, $on) {
                        if ((int) $person->job_position_id !== (int) $selectedPosition->id) {
                            return false;
                        }
                        if ($person->ended_at && $person->ended_at->toDateString() < $on->toDateString()) {
                            return false;
                        }

                        return true;
                    });
                @endphp
                <ul class="list-unstyled mb-0 small">
                    @forelse($assigned as $person)
                        <li class="d-flex justify-content-between gap-2 py-1 border-bottom">
                            <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}">{{ $person->fullName() }}</a>
                            <span class="text-muted">{{ $person->contract_type?->label() ?: '—' }}</span>
                        </li>
                    @empty
                        <li class="text-muted">Nema osoba na ovom mjestu na odabrani dan.</li>
                    @endforelse
                </ul>
            @else
                <p class="text-muted mb-0">Odaberite radno mjesto u šifrarniku.</p>
            @endif
        </div>
    </div>
@else
    <div class="d-flex justify-content-end mb-2">
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-mt">Novo mjesto troška</button>
    </div>
    <div class="table-responsive table-responsive-no-sticky">
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
                @forelse($filteredCostCenters as $costCenter)
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
                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                data-bs-toggle="modal" data-bs-target="#modal-mt"
                                data-ustroj-edit="mt"
                                data-action="{{ route('organization.structure.cost-centers.update', [$organization->slug, $costCenter]) }}"
                                data-code="{{ $costCenter->code }}"
                                data-name="{{ $costCenter->name }}"
                                data-from="{{ $costCenter->valid_from?->toDateString() }}"
                                data-to="{{ $costCenter->valid_to?->toDateString() }}">Uredi</button>
                            <form method="POST" action="{{ route('organization.structure.cost-centers.destroy', [$organization->slug, $costCenter]) }}" class="d-inline" onsubmit="return confirm('Obrisati mjesto troška?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema mjesta troška važećih na taj dan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

<div class="modal fade forma-modal" id="modal-odjel" tabindex="-1" aria-labelledby="modal-odjel-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.departments.store', $organization->slug) }}" class="modal-content" id="form-odjel">
            @csrf
            <input type="hidden" name="_method" value="POST" id="odjel-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-odjel-naslov">Novi odjel</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="dept_name">Naziv</label>
                        <input class="form-control" name="name" id="dept_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_code">Šifra</label>
                        <input class="form-control" name="code" id="dept_code">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dept_parent">Nadređeni odjel</label>
                        <select class="form-select" name="parent_id" id="dept_parent">
                            <option value="">— (vrh organizacije)</option>
                            @foreach($departments as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
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
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-mjesto" tabindex="-1" aria-labelledby="modal-mjesto-naslov">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('organization.structure.positions.store', $organization->slug) }}" class="modal-content" id="form-mjesto">
            @csrf
            <input type="hidden" name="_method" value="POST" id="mjesto-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-mjesto-naslov">Novo radno mjesto</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="forma-sekcija mt-0 pt-0 border-0">
                            <h2>Radno mjesto</h2>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="pos_name">Naziv</label>
                                <input class="form-control" name="name" id="pos_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="pos_department">Odjel</label>
                                <select class="form-select" name="department_id" id="pos_department">
                                    <option value="">—</option>
                                    @foreach($departments as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_rad1g">RAD1G</label>
                                <input class="form-control" name="rad1g" id="pos_rad1g">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_go">Fond GO</label>
                                <input type="number" min="0" max="50" class="form-control" name="annual_leave_days" id="pos_go">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_from">Važi od</label>
                                <input type="date" class="form-control" name="valid_from" id="pos_from" value="{{ $on->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_to">Važi do</label>
                                <input type="date" class="form-control" name="valid_to" id="pos_to">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="pos_description">Opis</label>
                                <textarea class="form-control" name="description" id="pos_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="forma-sekcija mt-0 pt-0 border-0">
                            <h2>Osobe na ovom mjestu</h2>
                            <p>Ugovor i datumi ostaju na kartici osobe.</p>
                        </div>
                        <pre class="small mb-0 bg-light rounded p-2" id="pos_people" style="white-space: pre-wrap; min-height: 8rem;">—</pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-mt" tabindex="-1" aria-labelledby="modal-mt-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.cost-centers.store', $organization->slug) }}" class="modal-content" id="form-mt">
            @csrf
            <input type="hidden" name="_method" value="POST" id="mt-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-mt-naslov">Novo mjesto troška</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
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
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function resetOdjel() {
        var form = document.getElementById('form-odjel');
        form.action = @json(route('organization.structure.departments.store', $organization->slug));
        document.getElementById('odjel-method').value = 'POST';
        document.getElementById('modal-odjel-naslov').textContent = 'Novi odjel';
        document.getElementById('dept_name').value = '';
        document.getElementById('dept_code').value = '';
        document.getElementById('dept_parent').value = '';
        document.getElementById('dept_manager').value = '';
        document.getElementById('dept_from').value = @json($on->toDateString());
        document.getElementById('dept_to').value = '';
    }
    function resetMjesto() {
        var form = document.getElementById('form-mjesto');
        form.action = @json(route('organization.structure.positions.store', $organization->slug));
        document.getElementById('mjesto-method').value = 'POST';
        document.getElementById('modal-mjesto-naslov').textContent = 'Novo radno mjesto';
        document.getElementById('pos_name').value = '';
        document.getElementById('pos_department').value = '';
        document.getElementById('pos_rad1g').value = '';
        document.getElementById('pos_go').value = '';
        document.getElementById('pos_description').value = '';
        document.getElementById('pos_from').value = @json($on->toDateString());
        document.getElementById('pos_to').value = '';
        document.getElementById('pos_people').textContent = '—';
    }
    function resetMt() {
        var form = document.getElementById('form-mt');
        form.action = @json(route('organization.structure.cost-centers.store', $organization->slug));
        document.getElementById('mt-method').value = 'POST';
        document.getElementById('modal-mt-naslov').textContent = 'Novo mjesto troška';
        document.getElementById('mt_code').value = '';
        document.getElementById('mt_name').value = '';
        document.getElementById('mt_from').value = @json($on->toDateString());
        document.getElementById('mt_to').value = '';
    }
    document.getElementById('modal-odjel').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetOdjel(); if (btn && btn.getAttribute('data-parent')) document.getElementById('dept_parent').value = btn.getAttribute('data-parent'); return; }
        document.getElementById('form-odjel').action = btn.getAttribute('data-action');
        document.getElementById('odjel-method').value = 'PUT';
        document.getElementById('modal-odjel-naslov').textContent = 'Uredi odjel';
        document.getElementById('dept_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('dept_code').value = btn.getAttribute('data-code') || '';
        document.getElementById('dept_parent').value = btn.getAttribute('data-parent') || '';
        document.getElementById('dept_manager').value = btn.getAttribute('data-manager') || '';
        document.getElementById('dept_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('dept_to').value = btn.getAttribute('data-to') || '';
    });
    document.getElementById('modal-mjesto').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetMjesto(); return; }
        document.getElementById('form-mjesto').action = btn.getAttribute('data-action');
        document.getElementById('mjesto-method').value = 'PUT';
        document.getElementById('modal-mjesto-naslov').textContent = 'Uredi radno mjesto';
        document.getElementById('pos_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('pos_department').value = btn.getAttribute('data-department') || '';
        document.getElementById('pos_rad1g').value = btn.getAttribute('data-rad1g') || '';
        document.getElementById('pos_go').value = btn.getAttribute('data-go') || '';
        document.getElementById('pos_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('pos_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('pos_to').value = btn.getAttribute('data-to') || '';
        document.getElementById('pos_people').textContent = btn.getAttribute('data-people') || '—';
    });
    document.getElementById('modal-mt').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetMt(); return; }
        document.getElementById('form-mt').action = btn.getAttribute('data-action');
        document.getElementById('mt-method').value = 'PUT';
        document.getElementById('modal-mt-naslov').textContent = 'Uredi mjesto troška';
        document.getElementById('mt_code').value = btn.getAttribute('data-code') || '';
        document.getElementById('mt_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('mt_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('mt_to').value = btn.getAttribute('data-to') || '';
    });
})();
</script>
@endpush
