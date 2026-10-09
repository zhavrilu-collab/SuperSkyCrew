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
                    <th>Stolice</th>
                    <th>GO</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($filteredPositions as $position)
                    @php
                        $seatRows = $position->orgPositions->filter(fn ($seat) => $seat->isValidOn($on));
                        $filledSeats = $seatRows->filter(fn ($seat) => $seat->status === \App\Enums\OrgSeatStatus::Filled || $seat->person_id)->count();
                        $openSeats = $seatRows->count() - $filledSeats;
                    @endphp
                    <tr class="{{ $selectedPosition?->id === $position->id ? 'ustroj-red-aktivan' : '' }}">
                        <td>
                            <a class="fw-semibold text-decoration-none" href="{{ $ustrojUrl('mjesta', $on->toDateString(), $q, $position->id) }}">{{ $position->name }}</a>
                        </td>
                        <td>{{ $position->department?->name ?: '—' }}</td>
                        <td>{{ $position->rad1gLabel() ?: '—' }}</td>
                        <td>
                            @if($seatRows->isEmpty())
                                —
                            @else
                                {{ $filledSeats }} popunjeno · {{ $openSeats }} otvoreno
                            @endif
                        </td>
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
                                data-pay="{{ $position->pay_grade }}"
                                data-duties="{{ $position->duties }}"
                                data-req="{{ $position->requirements }}"
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
                    <tr><td colspan="6" class="text-muted">Nema radnih mjesta važećih na taj dan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kartica-kontejner p-3 mb-0">
        @if($selectedPosition)
            <div class="forma-sekcija mt-0 pt-0 border-0">
                <h2>{{ $selectedPosition->name }}</h2>
                <p>{{ $selectedPosition->department?->name ?: 'Bez odjela' }}{{ $selectedPosition->rad1g ? ' · RAD1G '.$selectedPosition->rad1gLabel() : '' }}</p>
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
            @php
                $jobSeats = $selectedPosition->orgPositions->filter(fn ($seat) => $seat->isValidOn($on))->values();
            @endphp
            <div class="forma-sekcija">
                <h2>Stolice</h2>
                <p>Radno mjesto je uloga; stolica je konkretno mjesto na koje sjeda osoba.</p>
            </div>
            <ul class="list-unstyled mb-2 small">
                @forelse($jobSeats as $seat)
                    <li class="ustroj-stolica">
                        <span>
                            <strong>#{{ $seat->seat_no }}</strong>
                            · {{ $seat->status->label() }}
                            @if($seat->person)
                                · {{ $seat->person->fullName() }}
                            @endif
                        </span>
                        <span class="d-inline-flex flex-wrap gap-1 justify-content-end">
                            @if($seat->status !== \App\Enums\OrgSeatStatus::Hiring)
                                <form method="POST" action="{{ route('organization.positions.update', [$organization->slug, $seat]) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="{{ \App\Enums\OrgSeatStatus::Hiring->value }}">
                                    <button class="btn btn-outline-secondary btn-sm" type="submit">Zapošljavanje</button>
                                </form>
                            @endif
                            @if($seat->person_id)
                                <form method="POST" action="{{ route('organization.positions.update', [$organization->slug, $seat]) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="release" value="1">
                                    <button class="btn btn-outline-secondary btn-sm" type="submit">Skini</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('organization.positions.destroy', [$organization->slug, $seat]) }}" class="d-inline" onsubmit="return confirm('Ukloniti stolicu?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                </form>
                            @endif
                        </span>
                    </li>
                @empty
                    <li class="text-muted">Nema stolica na ovom mjestu. Otvorite stolicu za zapošljavanje.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('organization.positions.store', $organization->slug) }}">
                @csrf
                <input type="hidden" name="job_position_id" value="{{ $selectedPosition->id }}">
                <input type="hidden" name="status" value="{{ \App\Enums\OrgSeatStatus::Open->value }}">
                <input type="hidden" name="valid_from" value="{{ $on->toDateString() }}">
                @if($selectedPosition->department_id)
                    <input type="hidden" name="department_id" value="{{ $selectedPosition->department_id }}">
                @endif
                <button class="btn btn-primary btn-sm" type="submit">Otvori stolicu</button>
            </form>
        @else
            <p class="text-muted mb-0">Odaberite radno mjesto u šifrarniku.</p>
        @endif
    </div>
</div>
