@php
    $peopleCount = $people->filter(function ($person) use ($department, $on) {
        if ((int) $person->department_id !== (int) $department->id) {
            return false;
        }
        if ($person->ended_at && $person->ended_at->toDateString() < $on->toDateString()) {
            return false;
        }

        return true;
    })->count();
@endphp
<li>
    <div class="org-kutija org-kutija-funkcijska">
        <div class="org-kutija-kapa">{{ $department->name }}</div>
        <div class="org-kutija-tijelo">
            <span>{{ $department->manager?->name ?: 'bez voditelja' }}</span>
            <strong>{{ $peopleCount }} osoba</strong>
        </div>
        <div class="org-kutija-akcije">
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="modal" data-bs-target="#modal-odjel" data-parent="{{ $department->id }}" data-unit="{{ $department->enterprise_unit_id }}" title="Dodaj pododjel">+</button>
            <button class="btn btn-sm btn-light" type="button"
                data-bs-toggle="modal" data-bs-target="#modal-odjel"
                data-ustroj-edit="odjel"
                data-action="{{ route('organization.structure.departments.update', [$organization->slug, $department]) }}"
                data-name="{{ $department->name }}"
                data-code="{{ $department->code }}"
                data-parent="{{ $department->parent_id }}"
                data-unit="{{ $department->enterprise_unit_id }}"
                data-manager="{{ $department->manager_user_id }}"
                data-from="{{ $department->valid_from?->toDateString() }}"
                data-to="{{ $department->valid_to?->toDateString() }}">Uredi</button>
            <a class="btn btn-sm btn-light" href="{{ $ustrojUrl('mjesta', $on->toDateString(), null, false, $department->enterprise_unit_id) }}">Radna mjesta</a>
        </div>
    </div>
    @if($department->children->isNotEmpty())
        <ul>
            @foreach($department->children as $child)
                @include('organization.structure.shema-node', ['department' => $child])
            @endforeach
        </ul>
    @endif
</li>
