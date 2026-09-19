@extends('layouts.organization')

@section('title', $person ? 'Uredi osobu' : 'Nova osoba')
@section('nav-suffix', 'Kadrovi')

@section('content')
@php
    $tabUrl = $person
        ? fn (string $tab) => route('organization.people.edit', [$organization->slug, $person, 'tab' => $tab])
        : null;
    $nav = [
        'pregled' => 'Pregled',
        'odabir' => 'Odabir',
        'osobno' => 'Osobno',
        'zaposlenje' => 'Zaposlenje',
        'angazman' => 'Angažman',
        'ugovori' => 'Ugovori',
        'dokumenti' => 'Dokumenti',
        'kvalifikacije' => 'Kvalifikacije',
        'place' => 'Plaće i prava',
    ];
@endphp

<div class="page-heading">
    <h1>{{ $person ? $person->fullName() : 'Nova osoba' }}</h1>
    @if($person)
        <p class="text-muted mb-0">{{ $person->engagementLabel() }} · {{ $person->jobLabel() }}</p>
    @else
        <p class="text-muted mb-0">Kratki unos, zatim kartica s ostalim podacima.</p>
    @endif
</div>

@unless($person)
    <form method="POST" action="{{ route('organization.people.store', $organization->slug) }}" class="kartica-kontejner">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="first_name">Ime</label>
                <input class="form-control" name="first_name" id="first_name" value="{{ old('first_name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="last_name">Prezime</label>
                <input class="form-control" name="last_name" id="last_name" value="{{ old('last_name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" name="status" id="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', 'employee') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="department_id">Odjel</label>
                <select class="form-select" name="department_id" id="department_id">
                    <option value="">—</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="job_position_id">Radno mjesto</label>
                <select class="form-select" name="job_position_id" id="job_position_id">
                    <option value="">—</option>
                    @foreach($positions as $position)
                        <option value="{{ $position->id }}" @selected((string) old('job_position_id') === (string) $position->id)>{{ $position->summary() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="contract_type">Vrsta ugovora</label>
                <select class="form-select" name="contract_type" id="contract_type">
                    <option value="">—</option>
                    @foreach($contracts as $contract)
                        <option value="{{ $contract->value }}" @selected(old('contract_type') === $contract->value)>{{ $contract->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="started_at">Početak rada</label>
                <input type="date" class="form-control" name="started_at" id="started_at" value="{{ old('started_at') }}">
            </div>
            <div class="col-md-8">
                <label class="form-label" for="job_title">Naziv posla</label>
                <input class="form-control" name="job_title" id="job_title" value="{{ old('job_title') }}">
            </div>
            <div class="col-12">
                <div class="forma-sekcija">
                    <h2>Druge FO i honorarci (čl. 10. / UOD)</h2>
                    <p>Za druge FO obavezni su vrsta i naziv akta. Za honorarca unesite naziv UOD-a ili autorskog ugovora.</p>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="fo_kind">Vrsta FO</label>
                <select class="form-select" name="fo_kind" id="fo_kind">
                    <option value="">— (nije druga FO)</option>
                    @foreach($otherFoKinds as $kind)
                        <option value="{{ $kind->value }}" @selected(old('fo_kind') === $kind->value)>{{ $kind->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="instrument_title">Naziv ugovora / akta</label>
                <input class="form-control" name="instrument_title" id="instrument_title" value="{{ old('instrument_title') }}" maxlength="255" placeholder="npr. Ugovor o studentskom poslu / Ugovor o djelu">
            </div>
            <div class="col-12">
                <div class="forma-sekcija">
                    <h2>Ustupljeni i rukovodeće</h2>
                    <p>Ustupljeni (agencija / povezano društvo) ulaze u pisani pregled čl. 4. Rukovodeća s ugovorenom samostalnošću ima smanjeni slog RV (čl. 21.).</p>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="host_employer">Ustupitelj / agencija</label>
                <input class="form-control" name="host_employer" id="host_employer" value="{{ old('host_employer') }}" maxlength="255" placeholder="obavezno za ustupljenog radnika">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="assignment_clocks" id="assignment_clocks" value="1" @checked(old('assignment_clocks', true))>
                    <label class="form-check-label" for="assignment_clocks">Evidencija RV ugovorena</label>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="executive_autonomy" id="executive_autonomy" value="1" @checked(old('executive_autonomy'))>
                    <label class="form-check-label" for="executive_autonomy">Samostalnost (čl. 21.)</label>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="citizenship">Državljanstvo</label>
                <input class="form-control" name="citizenship" id="citizenship" value="{{ old('citizenship') }}">
            </div>
        </div>
        <div class="forma-podnozje">
            <a class="btn btn-outline-secondary" href="{{ route('organization.people.index', $organization->slug) }}">Odustani</a>
            <button class="btn btn-primary" type="submit">Spremi i otvori karticu</button>
        </div>
    </form>
@else
    <div class="profil-osobe">
        <div class="kartica-kontejner profil-identitet mb-0">
            <div class="profil-avatar" aria-hidden="true">{{ $person->initials() }}</div>
            <div class="text-center">
                <div class="fw-semibold">{{ $person->fullName() }}</div>
                <div class="small text-muted">{{ $person->jobLabel() }}</div>
                <div class="mt-2"><span class="badge text-bg-success">{{ $person->status->label() }}</span></div>
                @if($person->user?->email)
                    <div class="small text-muted mt-2">{{ $person->user->email }}</div>
                @endif
                @if($person->oib)
                    <div class="small text-muted">OIB {{ $person->oib }}</div>
                @endif
            </div>
            <ul class="profil-nav">
                @foreach($nav as $key => $label)
                    <li>
                        <a class="{{ $profileTab === $key ? 'active' : '' }}" href="{{ $tabUrl($key) }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="d-grid gap-2 mt-3">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.people.review', [$organization->slug, $person]) }}">Pisani pregled</a>
                @if($person->isClockEligible())
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.people.badge', [$organization->slug, $person]) }}">Iskaznica (QR)</a>
                @endif
                @if($person->status->usesEmploymentContract())
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.people.contract', [$organization->slug, $person]) }}">UOR</a>
                @endif
                @if($person->status !== \App\Enums\PersonStatus::Volunteer && $person->status !== \App\Enums\PersonStatus::Candidate)
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.people.referral', [$organization->slug, $person]) }}">Uputnica</a>
                @endif
                @if($person->status === \App\Enums\PersonStatus::Candidate)
                    <form method="POST" action="{{ route('organization.people.hire', [$organization->slug, $person]) }}">
                        @csrf
                        <button class="btn btn-outline-success btn-sm w-100" type="submit">Prenesi u kadar</button>
                    </form>
                @endif
            </div>
        </div>

        <div>
            @if($profileTab === 'pregled')
                <div class="kartica-kontejner">
                    <div class="forma-sekcija mt-0 pt-0 border-0">
                        <h2>Glavno radno mjesto</h2>
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Odjel</dt><dd class="col-sm-8">{{ $person->department?->name ?: '—' }}</dd>
                        <dt class="col-sm-4">Radno mjesto</dt><dd class="col-sm-8">{{ $person->jobLabel() }}</dd>
                        <dt class="col-sm-4">Voditelj</dt><dd class="col-sm-8">{{ $person->manager?->name ?: '—' }}</dd>
                        <dt class="col-sm-4">Lokacija</dt><dd class="col-sm-8">{{ $person->location?->name ?: '—' }}</dd>
                        <dt class="col-sm-4">Mjesto troška</dt><dd class="col-sm-8">{{ $person->costCenter?->summary() ?: '—' }}</dd>
                        <dt class="col-sm-4">Početak</dt><dd class="col-sm-8">{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</dd>
                    </dl>
                    <div class="row g-2 mt-3">
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('odabir') }}">
                                <div class="kpi-label">CV</div>
                                <div class="kpi-broj">{{ $person->hasCv() ? 'da' : 'ne' }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('odabir') }}">
                                <div class="kpi-label">Razgovori</div>
                                <div class="kpi-broj">{{ $person->interviewNotes->count() }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('angazman') }}">
                                <div class="kpi-label">Razdoblja</div>
                                <div class="kpi-broj">{{ $person->engagements->count() }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('ugovori') }}">
                                <div class="kpi-label">Ugovori</div>
                                <div class="kpi-broj">{{ $person->employmentContracts->count() }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('dokumenti') }}">
                                <div class="kpi-label">Dokumenti dosjea</div>
                                <div class="kpi-broj">{{ $person->documents->count() }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('kvalifikacije') }}">
                                <div class="kpi-label">Kvalifikacije</div>
                                <div class="kpi-broj">{{ $person->qualifications->count() }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ $tabUrl('zaposlenje') }}">
                                <div class="kpi-label">GO preostalo</div>
                                <div class="kpi-broj">{{ data_get($leaveSnapshot, 'remaining', '—') }}</div>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <a class="profil-plocica" href="{{ route('organization.expiries.index', $organization->slug) }}">
                                <div class="kpi-label">Isteci 30 d.</div>
                                <div class="kpi-broj">{{ $expiryCount }}</div>
                            </a>
                        </div>
                    </div>
                </div>
            @elseif(in_array($profileTab, ['osobno', 'zaposlenje', 'place'], true))
                <form method="POST" action="{{ route('organization.people.update', [$organization->slug, $person]) }}" class="kartica-kontejner">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_tab" value="{{ $profileTab }}">
                    @include('organization.people.partials.polja')
                    <div class="forma-podnozje">
                        <a class="btn btn-outline-secondary" href="{{ route('organization.people.index', $organization->slug) }}">Odustani</a>
                        <button class="btn btn-primary" type="submit">Spremi</button>
                    </div>
                </form>
            @elseif($profileTab === 'odabir')
                @include('organization.people.partials.odabir')
            @elseif($profileTab === 'ugovori')
                @include('organization.people.partials.ugovori')
            @elseif($profileTab === 'dokumenti')
                @include('organization.people.partials.dokumenti')
            @elseif($profileTab === 'angazman')
                @include('organization.people.partials.angazman')
            @else
                @include('organization.people.partials.kvalifikacije')
            @endif
        </div>
    </div>
@endunless
@endsection
