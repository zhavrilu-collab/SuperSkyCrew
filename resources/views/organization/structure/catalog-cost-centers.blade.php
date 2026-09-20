<div class="table-responsive table-responsive-no-sticky p-3">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th>Šifra</th>
                <th>Naziv</th>
                <th>Pravna osoba</th>
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
                    <td>{{ $costCenter->legalEntity?->name ?: '—' }}</td>
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
                            data-legal="{{ $costCenter->legal_entity_id }}"
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
                <tr><td colspan="6" class="text-muted">Nema mjesta troška važećih na taj dan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
