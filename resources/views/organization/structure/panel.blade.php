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
        || str_contains(mb_strtolower((string) $position->rad1g), $qLower));
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

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h5 mb-1">Struktura organizacije</h2>
        <p class="text-muted mb-0">Pravne osobe, poslovnice, poslovna i funkcijska struktura, radna mjesta i organigram. Pregled stanja na dan.</p>
    </div>
    <form method="GET" action="{{ route('organization.settings.index', $organization->slug) }}" class="ustroj-toolbar">
        <input type="hidden" name="tab" value="organizacija">
        <input type="hidden" name="section" value="ustroj">
        <input type="hidden" name="katalog" value="{{ $katalog }}">
        @if($selectedUnit)
            <input type="hidden" name="jedinica" value="{{ $selectedUnit->id }}">
        @endif
        <div>
            <label class="form-label mb-1" for="na">Stanje na dan</label>
            <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
        </div>
        @if(! in_array($katalog, ['poslovna', 'funkcijska', 'organigram'], true))
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

@include('organization.structure.modals')
