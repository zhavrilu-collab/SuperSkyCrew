<li>
    <div class="org-kutija org-kutija-poslovna">
        <div class="org-kutija-kapa">{{ $unit->name }}</div>
        <div class="org-kutija-tijelo">
            <span>{{ $unit->caption() }}</span>
            <strong>{{ $departments->where('enterprise_unit_id', $unit->id)->count() }} odjela</strong>
        </div>
        <div class="org-kutija-akcije">
            <button class="org-kutija-akcija" type="button" data-bs-toggle="modal" data-bs-target="#modal-jedinica" data-parent="{{ $unit->id }}" title="Dodaj podređenu">+</button>
            <button class="org-kutija-akcija" type="button"
                data-bs-toggle="modal" data-bs-target="#modal-jedinica"
                data-ustroj-edit="jedinica"
                data-action="{{ route('organization.structure.enterprise-units.update', [$organization->slug, $unit]) }}"
                data-name="{{ $unit->name }}"
                data-parent="{{ $unit->parent_id }}"
                data-legal="{{ $unit->legal_entity_id }}"
                data-work="{{ $unit->work_center_id }}"
                data-from="{{ $unit->valid_from?->toDateString() }}"
                data-to="{{ $unit->valid_to?->toDateString() }}"
                title="Uredi">✎</button>
            <a class="org-kutija-akcija" href="{{ $ustrojUrl('funkcijska', $on->toDateString(), null, false, $unit->id) }}" title="Funkcionalni ustroj">↗</a>
        </div>
    </div>
    @if($unit->children->isNotEmpty())
        <ul>
            @foreach($unit->children as $child)
                @include('organization.structure.enterprise-node', ['unit' => $child])
            @endforeach
        </ul>
    @endif
</li>
