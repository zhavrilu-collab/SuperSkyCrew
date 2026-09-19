<div class="d-flex justify-content-end mb-2">
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modal-pravna">Nova pravna osoba</button>
</div>
<div class="table-responsive table-responsive-no-sticky">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th>Naziv</th>
                <th>Šifra</th>
                <th>OIB</th>
                <th>Sjedište</th>
                <th>Važenje</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($filteredLegal as $entity)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $entity->name }}</div>
                        @if($entity->parent)
                            <div class="text-muted small">{{ $entity->parent->name }}</div>
                        @endif
                    </td>
                    <td>{{ $entity->code ?: '—' }}</td>
                    <td>{{ $entity->oib ?: '—' }}</td>
                    <td class="small">{{ collect([$entity->street, $entity->city, $entity->country])->filter()->implode(', ') ?: '—' }}</td>
                    <td class="small">{{ $entity->valid_from?->format('d.m.Y.') ?: '—' }} – {{ $entity->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}</td>
                    <td class="text-end">
                        <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="modal" data-bs-target="#modal-pravna"
                            data-ustroj-edit="pravna"
                            data-action="{{ route('organization.structure.legal-entities.update', [$organization->slug, $entity]) }}"
                            data-name="{{ $entity->name }}"
                            data-code="{{ $entity->code }}"
                            data-oib="{{ $entity->oib }}"
                            data-parent="{{ $entity->parent_id }}"
                            data-street="{{ $entity->street }}"
                            data-city="{{ $entity->city }}"
                            data-country="{{ $entity->country }}"
                            data-from="{{ $entity->valid_from?->toDateString() }}"
                            data-to="{{ $entity->valid_to?->toDateString() }}">Uredi</button>
                        <form method="POST" action="{{ route('organization.structure.legal-entities.destroy', [$organization->slug, $entity]) }}" class="d-inline" onsubmit="return confirm('Obrisati pravnu osobu?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Nema pravnih osoba važećih na taj dan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
