<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label mb-1" for="org-jedinica">Poslovna jedinica</label>
        <select class="form-select" id="org-jedinica" onchange="window.location = this.value">
            @foreach($enterpriseUnits as $unit)
                <option value="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $unit->id, null, null) }}" @selected($selectedUnit?->id === $unit->id)>{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label mb-1" for="org-odjel">Odjel</label>
        <select class="form-select" id="org-odjel" onchange="window.location = this.value">
            <option value="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $selectedUnit?->id, null, null) }}">Svi odjeli</option>
            @foreach($functionalDepartments as $department)
                <option value="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $selectedUnit?->id, $department->id, null) }}" @selected($selectedDepartment?->id === $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label mb-1" for="org-osoba">Osoba</label>
        <select class="form-select" id="org-osoba" onchange="window.location = this.value">
            <option value="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $selectedUnit?->id, $selectedDepartment?->id, null) }}">Sve osobe</option>
            @foreach($people->filter(fn ($person) => ! $person->ended_at || $person->ended_at->toDateString() >= $on->toDateString()) as $person)
                <option value="{{ $ustrojUrl('organigram', $on->toDateString(), null, false, $selectedUnit?->id, $selectedDepartment?->id, $person->id) }}" @selected((int) $selectedPersonId === (int) $person->id)>{{ $person->fullName() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="org-shema org-shema-ljudi">
    <ul>
        @forelse($chartForest as $person)
            @include('organization.structure.person-node', ['person' => $person])
        @empty
            <li>
                <div class="org-kutija org-kutija-osoba">
                    <div class="org-kutija-kapa">Nema osoba</div>
                    <div class="org-kutija-tijelo"><span>Nema kadra za odabrani filter.</span></div>
                </div>
            </li>
        @endforelse
    </ul>
</div>
