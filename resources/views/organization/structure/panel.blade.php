@php
    $ustrojUrl = fn (?string $kat = null, ?string $na = null, ?string $q = null, $mjesto = false, $jedinica = false, $odjel = false, $osoba = false) => route('organization.settings.index', array_filter([
        'slug' => $organization->slug,
        'tab' => 'organizacija',
        'section' => 'ustroj',
        'katalog' => $kat ?? $katalog,
        'na' => ($na ?? $on->toDateString()),
        'q' => $q,
        'mjesto' => ($kat ?? $katalog) === 'mjesta'
            ? ($mjesto === false ? $selectedPosition?->id : $mjesto)
            : null,
        'jedinica' => in_array($kat ?? $katalog, ['funkcijska', 'mjesta', 'organigram'], true)
            ? ($jedinica === false ? $selectedUnit?->id : $jedinica)
            : null,
        'odjel' => ($kat ?? $katalog) === 'organigram'
            ? ($odjel === false ? $selectedDepartment?->id : $odjel)
            : null,
        'osoba' => ($kat ?? $katalog) === 'organigram'
            ? ($osoba === false ? $selectedPersonId : $osoba)
            : null,
    ], fn ($value) => $value !== null && $value !== ''));
    $tabs = \App\Support\StructureCatalog::tabs();
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
        || str_contains(mb_strtolower((string) $position->rad1g), $qLower)
        || str_contains(mb_strtolower((string) $position->rad1gLabel()), $qLower));
    $filteredCostCenters = $q === '' ? $costCenters : $costCenters->filter(fn ($costCenter) => str_contains(mb_strtolower($costCenter->name), $qLower)
        || str_contains(mb_strtolower($costCenter->code), $qLower));
    $filteredLegal = $q === '' ? $legalEntities : $legalEntities->filter(fn ($entity) => str_contains(mb_strtolower($entity->name), $qLower)
        || str_contains(mb_strtolower((string) $entity->code), $qLower)
        || str_contains((string) $entity->oib, $qLower));
    $filteredWorkCenters = $q === '' ? $workCenters : $workCenters->filter(fn ($center) => str_contains(mb_strtolower($center->name), $qLower)
        || str_contains(mb_strtolower((string) $center->code), $qLower)
        || str_contains(mb_strtolower((string) $center->city), $qLower));
    $forest = \App\Support\DepartmentTree::forest($functionalDepartments);
    $selectedPosition = $selectedPosition ?? null;
@endphp

@php
    $slojNaslov = $tabs[$katalog] ?? 'Ustroj tvrtke';
    $dodajModal = match ($katalog) {
        'pravne' => ['#modal-pravna', 'Nova pravna osoba'],
        'poslovnice' => ['#modal-poslovnica', 'Nova poslovnica'],
        'troskovi' => ['#modal-mt', 'Novo mjesto troška'],
        'poslovna' => ['#modal-jedinica', 'Nova poslovna jedinica'],
        'funkcijska' => ['#modal-odjel', 'Novi odjel'],
        'mjesta' => ['#modal-mjesto', 'Novo radno mjesto'],
        default => null,
    };
@endphp
<div class="org-platno">
    <div class="ustroj-kontekst">
        <div>
            <h2>{{ $slojNaslov }}</h2>
            <p>Stanje na dan {{ $on->format('d.m.Y.') }}</p>
        </div>
        <form method="GET" action="{{ route('organization.settings.index', $organization->slug) }}" class="ustroj-toolbar">
            <input type="hidden" name="tab" value="organizacija">
            <input type="hidden" name="section" value="ustroj">
            <input type="hidden" name="katalog" value="{{ $katalog }}">
            <div>
                <label class="form-label mb-1" for="na">Stanje na dan</label>
                <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
            </div>
            @if(in_array($katalog, ['funkcijska', 'mjesta', 'organigram'], true) && $enterpriseUnits->isNotEmpty())
                <div>
                    <label class="form-label mb-1" for="kontekst-jedinica">Poslovna jedinica</label>
                    <select class="form-select" id="kontekst-jedinica" name="jedinica" onchange="this.form.submit()">
                        @foreach($enterpriseUnits as $unit)
                            <option value="{{ $unit->id }}" @selected($selectedUnit?->id === $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($katalog === 'organigram')
                <div>
                    <label class="form-label mb-1" for="kontekst-odjel">Odjel</label>
                    <select class="form-select" id="kontekst-odjel" name="odjel" onchange="this.form.submit()">
                        <option value="">Svi odjeli</option>
                        @foreach($functionalDepartments as $department)
                            <option value="{{ $department->id }}" @selected($selectedDepartment?->id === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label mb-1" for="kontekst-osoba">Osoba</label>
                    <select class="form-select" id="kontekst-osoba" name="osoba" onchange="this.form.submit()">
                        <option value="">Sve osobe</option>
                        @foreach($people->filter(fn ($person) => ! $person->ended_at || $person->ended_at->toDateString() >= $on->toDateString()) as $person)
                            <option value="{{ $person->id }}" @selected((int) $selectedPersonId === (int) $person->id)>{{ $person->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(! in_array($katalog, ['poslovna', 'funkcijska', 'organigram'], true))
                <div>
                    <label class="form-label mb-1" for="q">Pretraga</label>
                    <input class="form-control" name="q" id="q" value="{{ $q }}" placeholder="Naziv ili šifra">
                </div>
            @endif
            <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
            @if($dodajModal)
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="{{ $dodajModal[0] }}"
                    @if($katalog === 'funkcijska' && $selectedUnit) data-unit="{{ $selectedUnit->id }}" @endif
                    @if($katalog === 'mjesta' && $selectedDepartment) data-department="{{ $selectedDepartment->id }}" @endif>
                    {{ $dodajModal[1] }}
                </button>
            @endif
        </form>
    </div>

    @if(($scheduledChanges ?? []) !== [])
        <div class="kartica-kontejner mx-3 mb-3">
            <h2 class="h6">Zakazane buduće promjene</h2>
            <p class="text-muted small">Zapisi s datumom važenja u budućnosti. Pregled na taj dan: polje „Stanje na dan“.</p>
            <ul class="mb-0 small">
                @foreach($scheduledChanges as $change)
                    <li>{{ $change['from'] }} · {{ $change['type'] }}: {{ $change['name'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($katalog === 'pravne')
        @include('organization.structure.catalog-legal')
    @elseif($katalog === 'poslovnice')
        @include('organization.structure.catalog-work-centers')
    @elseif($katalog === 'troskovi')
        @include('organization.structure.catalog-cost-centers')
    @elseif($katalog === 'poslovna')
        @include('organization.structure.tree-enterprise')
    @elseif($katalog === 'funkcijska')
        @include('organization.structure.tree-functional')
    @elseif($katalog === 'mjesta')
        @include('organization.structure.tree-jobs')
    @else
        @include('organization.structure.tree-organigram')
    @endif
</div>

@include('organization.structure.modals')
