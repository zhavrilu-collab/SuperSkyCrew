<div class="d-flex justify-content-end mb-0 px-3 pt-3">
    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#odjeli-tablica">Tablica odjela</button>
</div>
<div class="org-shema org-shema-funkcijska">
    <ul>
        <li>
            <div class="org-kutija org-kutija-root">
                <div class="org-kutija-kapa">{{ $selectedUnit?->name ?: $organization->name }}</div>
                <div class="org-kutija-tijelo">
                    <span>{{ $functionalDepartments->count() }} odjela</span>
                    <strong>{{ $people->whereIn('department_id', $functionalDepartments->pluck('id'))->count() }} osoba</strong>
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
<div class="collapse mt-3 px-3 pb-3" id="odjeli-tablica">
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
                @forelse($functionalDepartmentRows as $row)
                    @php $department = $row['department']; @endphp
                    <tr>
                        <td style="padding-left: {{ 6 + ($row['depth'] * 16) }}px">
                            <div class="fw-semibold">{{ $department->name }}</div>
                            <div class="text-muted small">{{ $department->code ?: 'bez šifre' }}</div>
                        </td>
                        <td>{{ $department->manager?->name ?: '—' }}</td>
                        <td class="small">{{ $department->valid_from?->format('d.m.Y.') ?: '—' }} – {{ $department->valid_to?->format('d.m.Y.') ?: 'otvoreno' }}</td>
                        <td>{{ $peopleCount($department->id) }}</td>
                        <td class="text-end">
                            <button class="btn btn-outline-secondary btn-sm" type="button"
                                data-bs-toggle="modal" data-bs-target="#modal-odjel"
                                data-ustroj-edit="odjel"
                                data-action="{{ route('organization.structure.departments.update', [$organization->slug, $department]) }}"
                                data-name="{{ $department->name }}"
                                data-code="{{ $department->code }}"
                                data-parent="{{ $department->parent_id }}"
                                data-unit="{{ $department->enterprise_unit_id }}"
                                data-manager="{{ $department->manager_user_id }}"
                                data-deputy="{{ $department->deputy_user_id }}"
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
</div>
