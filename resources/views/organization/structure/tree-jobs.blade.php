<div class="org-shema org-shema-mjesta">
    <ul>
        @forelse($forest as $department)
            @include('organization.structure.job-node', ['department' => $department])
        @empty
            <li>
                <div class="org-kutija org-kutija-mjesto">
                    <div class="org-kutija-kapa">Nema odjela</div>
                    <div class="org-kutija-tijelo"><span>Dodajte odjel u funkcijskom ustroju.</span></div>
                </div>
            </li>
        @endforelse
    </ul>
</div>
<div class="ustroj-split mt-0 p-3">
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
