<div class="kartica-kontejner mb-3">
    <form method="GET" action="{{ route('organization.systematization.index', $organization->slug) }}" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label mb-1" for="na">Stanje na dan</label>
            <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
        </div>
        <div>
            <label class="form-label mb-1" for="q">Pretraga</label>
            <input class="form-control" name="q" id="q" value="{{ $q }}" placeholder="Naziv, odjel ili RAD1G">
        </div>
        @if($selectedPosition)
            <input type="hidden" name="mjesto" value="{{ $selectedPosition->id }}">
        @endif
        <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
    </form>
</div>

<div class="ustroj-split">
    <div class="kartica-kontejner p-0 overflow-hidden mb-0">
        <div class="table-responsive table-responsive-no-sticky">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Radno mjesto</th>
                        <th>Odjel</th>
                        <th>RAD1G</th>
                        <th>Popunjeno</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($positions as $position)
                        <tr class="{{ $selectedPosition?->id === $position->id ? 'ustroj-red-aktivan' : '' }}">
                            <td>
                                <a class="fw-semibold text-decoration-none" href="{{ $sistemaUrl('opisi', $position->id, $on->toDateString(), $q) }}">{{ $position->name }}</a>
                                @if($position->duties)
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($position->duties, 80) }}</div>
                                @endif
                            </td>
                            <td>{{ $position->department?->name ?: '—' }}</td>
                            <td>{{ $position->rad1gLabel() ?: '—' }}</td>
                            <td>{{ (int) ($filled[$position->id] ?? 0) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $ustrojMjestoUrl($position->id) }}">Ustroj</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">Nema radnih mjesta na odabrani dan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="kartica-kontejner p-3 mb-0">
        @if($selectedPosition)
            @php
                $availableCompetencies = $competencies->reject(
                    fn ($competency) => $selectedPosition->competencies->contains('id', $competency->id)
                );
            @endphp
            <div class="forma-sekcija mt-0 pt-0 border-0">
                <h2>{{ $selectedPosition->name }}</h2>
                <p>{{ $selectedPosition->department?->name ?: 'Bez odjela' }}{{ $selectedPosition->rad1g ? ' · RAD1G '.$selectedPosition->rad1gLabel() : '' }}</p>
            </div>
            @if($selectedPosition->description)
                <p class="small text-muted">{{ $selectedPosition->description }}</p>
            @endif
            @if($selectedPosition->duties)
                <p class="small mb-2"><span class="text-muted">Dužnosti:</span> {{ $selectedPosition->duties }}</p>
            @endif
            @if($selectedPosition->requirements)
                <p class="small mb-2"><span class="text-muted">Zahtjevi:</span> {{ $selectedPosition->requirements }}</p>
            @endif
            <div class="forma-sekcija">
                <h2>Kompetencije</h2>
                <p>Vještine potrebne za ovu ulogu. Certifikati osobe ostaju na kartici djelatnika.</p>
            </div>
            <ul class="list-unstyled mb-2 small">
                @forelse($selectedPosition->competencies as $competency)
                    <li class="ustroj-stolica">
                        <span>{{ $competency->name }} · {{ $competency->kind->label() }} ({{ $competency->pivot->required_level }})</span>
                        <form method="POST" action="{{ route('organization.competencies.detach', $organization->slug) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="job_position_id" value="{{ $selectedPosition->id }}">
                            <input type="hidden" name="competency_id" value="{{ $competency->id }}">
                            <button class="btn btn-link btn-sm p-0" type="submit">Skini</button>
                        </form>
                    </li>
                @empty
                    <li class="text-muted">Nema vezanih kompetencija.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('organization.competencies.attach', $organization->slug) }}" class="row g-2 align-items-end mb-3">
                @csrf
                <input type="hidden" name="job_position_id" value="{{ $selectedPosition->id }}">
                <div class="col-7">
                    <label class="form-label mb-1" for="competency_id">Veži kompetenciju</label>
                    <select class="form-select form-select-sm" name="competency_id" id="competency_id" required>
                        <option value="">—</option>
                        @foreach($availableCompetencies as $competency)
                            <option value="{{ $competency->id }}">{{ $competency->name }} · {{ $competency->kind->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-3">
                    <label class="form-label mb-1" for="required_level">Razina</label>
                    <input type="number" min="1" max="5" class="form-control form-control-sm" name="required_level" id="required_level" value="3" required>
                </div>
                <div class="col-2">
                    <button class="btn btn-outline-primary btn-sm w-100" type="submit" @disabled($availableCompetencies->isEmpty())>Veži</button>
                </div>
            </form>
            <div class="forma-sekcija">
                <h2>Šifrarnik</h2>
                <p>Nova vještina u katalogu, pa je vežite na ulogu.</p>
            </div>
            <form method="POST" action="{{ route('organization.competencies.store', $organization->slug) }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="mjesto" value="{{ $selectedPosition->id }}">
                <div class="col-6">
                    <label class="form-label mb-1" for="name">Naziv</label>
                    <input class="form-control form-control-sm" name="name" id="name" required>
                </div>
                <div class="col-4">
                    <label class="form-label mb-1" for="kind">Vrsta</label>
                    <select class="form-select form-select-sm" name="kind" id="kind">
                        @foreach($competencyKinds as $kind)
                            <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-2">
                    <button class="btn btn-primary btn-sm w-100" type="submit">Dodaj</button>
                </div>
            </form>
        @else
            <p class="text-muted mb-0">Odaberite radno mjesto u katalogu.</p>
        @endif
    </div>
</div>
