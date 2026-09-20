<div class="table-responsive table-responsive-no-sticky p-3">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th>Naziv</th>
                <th>Pravna osoba</th>
                <th>Lokacija (prijava)</th>
                <th>Grad</th>
                <th>Važenje</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($filteredWorkCenters as $center)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $center->name }}</div>
                        <div class="text-muted small">{{ $center->code ?: 'bez šifre' }}</div>
                    </td>
                    <td>{{ $center->legalEntity?->name ?: '—' }}</td>
                    <td>{{ $center->location?->name ?: '—' }}</td>
                    <td>{{ $center->city ?: '—' }}</td>
                    <td class="small">{{ $center->valid_from?->format('d.m.Y.') ?: '—' }} – {{ $center->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}</td>
                    <td class="text-end">
                        <button class="btn btn-outline-secondary btn-sm" type="button"
                            data-bs-toggle="modal" data-bs-target="#modal-poslovnica"
                            data-ustroj-edit="poslovnica"
                            data-action="{{ route('organization.structure.work-centers.update', [$organization->slug, $center]) }}"
                            data-name="{{ $center->name }}"
                            data-code="{{ $center->code }}"
                            data-legal="{{ $center->legal_entity_id }}"
                            data-location="{{ $center->location_id }}"
                            data-street="{{ $center->street }}"
                            data-city="{{ $center->city }}"
                            data-from="{{ $center->valid_from?->toDateString() }}"
                            data-to="{{ $center->valid_to?->toDateString() }}">Uredi</button>
                        <form method="POST" action="{{ route('organization.structure.work-centers.destroy', [$organization->slug, $center]) }}" class="d-inline" onsubmit="return confirm('Obrisati poslovnicu?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Nema poslovnica važećih na taj dan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
