@extends('layouts.organization')

@section('title', $person ? 'Uredi osobu' : 'Nova osoba')

@section('content')
<h1 class="h4 mb-4">{{ $person ? 'Uredi karticu' : 'Nova osoba' }}</h1>

<form method="POST" action="{{ $person ? route('organization.people.update', [$organization->slug, $person]) : route('organization.people.store', $organization->slug) }}" class="card border-0 shadow-sm">
    @csrf
    @if($person)
        @method('PUT')
    @endif
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="first_name">Ime</label>
                <input class="form-control" name="first_name" id="first_name" value="{{ old('first_name', $person?->first_name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="last_name">Prezime</label>
                <input class="form-control" name="last_name" id="last_name" value="{{ old('last_name', $person?->last_name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="oib">OIB</label>
                <input class="form-control" name="oib" id="oib" value="{{ old('oib', $person?->oib) }}" maxlength="11">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="gender">Spol</label>
                <select class="form-select" name="gender" id="gender">
                    <option value="">—</option>
                    <option value="m" @selected(old('gender', $person?->gender) === 'm')>M</option>
                    <option value="z" @selected(old('gender', $person?->gender) === 'z')>Ž</option>
                    <option value="x" @selected(old('gender', $person?->gender) === 'x')>Ostalo</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="date_of_birth">Datum rođenja</label>
                <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $person?->date_of_birth?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="citizenship">Državljanstvo</label>
                <input class="form-control" name="citizenship" id="citizenship" value="{{ old('citizenship', $person?->citizenship) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" name="status" id="status" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $person?->status?->value ?? 'employee') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="residence">Prebivalište / boravište</label>
                <input class="form-control" name="residence" id="residence" value="{{ old('residence', $person?->residence) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="department_id">Odjel</label>
                <select class="form-select" name="department_id" id="department_id">
                    <option value="">—</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $person?->department_id) === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="job_position_id">Radno mjesto (šifrarnik)</label>
                <select class="form-select" name="job_position_id" id="job_position_id">
                    <option value="">—</option>
                    @foreach($positions as $position)
                        <option value="{{ $position->id }}" @selected((string) old('job_position_id', $person?->job_position_id) === (string) $position->id)>{{ $position->summary() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cost_center_id">Mjesto troška</label>
                <select class="form-select" name="cost_center_id" id="cost_center_id">
                    <option value="">—</option>
                    @foreach($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}" @selected((string) old('cost_center_id', $person?->cost_center_id) === (string) $costCenter->id)>{{ $costCenter->summary() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="job_title">Naziv posla</label>
                <input class="form-control" name="job_title" id="job_title" value="{{ old('job_title', $person?->job_title) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="contract_type">Vrsta ugovora</label>
                <select class="form-select" name="contract_type" id="contract_type">
                    <option value="">—</option>
                    @foreach($contracts as $contract)
                        <option value="{{ $contract->value }}" @selected(old('contract_type', $person?->contract_type?->value) === $contract->value)>{{ $contract->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="started_at">Početak rada</label>
                <input type="date" class="form-control" name="started_at" id="started_at" value="{{ old('started_at', $person?->started_at?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ended_at">Prestanak</label>
                <input type="date" class="form-control" name="ended_at" id="ended_at" value="{{ old('ended_at', $person?->ended_at?->toDateString()) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ended_reason">Razlog prestanka</label>
                <input class="form-control" name="ended_reason" id="ended_reason" value="{{ old('ended_reason', $person?->ended_reason) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="insurance_filed_at">Prijava na osiguranja</label>
                <input type="date" class="form-control" name="insurance_filed_at" id="insurance_filed_at" value="{{ old('insurance_filed_at', $person?->insurance_filed_at?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="work_permit_expires_at">Istek dozvole</label>
                <input type="date" class="form-control" name="work_permit_expires_at" id="work_permit_expires_at" value="{{ old('work_permit_expires_at', $person?->work_permit_expires_at?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="medical_expires_at">Istek liječničkog</label>
                <input type="date" class="form-control" name="medical_expires_at" id="medical_expires_at" value="{{ old('medical_expires_at', $person?->medical_expires_at?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="certificate_expires_at">Istek certifikata</label>
                <input type="date" class="form-control" name="certificate_expires_at" id="certificate_expires_at" value="{{ old('certificate_expires_at', $person?->certificate_expires_at?->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="location_id">Lokacija</label>
                <select class="form-select" name="location_id" id="location_id">
                    <option value="">—</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) old('location_id', $person?->location_id) === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="user_id">Korisnički račun</label>
                <select class="form-select" name="user_id" id="user_id">
                    <option value="">nije povezano</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('user_id', $person?->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="manager_user_id">Voditelj</label>
                <select class="form-select" name="manager_user_id" id="manager_user_id">
                    <option value="">—</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) old('manager_user_id', $person?->manager_user_id) === (string) $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="annual_leave_days">Fond GO (dana)</label>
                <input type="number" min="0" max="50" class="form-control" name="annual_leave_days" id="annual_leave_days" value="{{ old('annual_leave_days', $person?->annual_leave_days ?? 20) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="clock_pin">PIN za kiosk</label>
                <input class="form-control" name="clock_pin" id="clock_pin" value="{{ old('clock_pin', $person?->clock_pin) }}" maxlength="6" inputmode="numeric">
            </div>
            <div class="col-12">
                <hr class="mt-2 mb-1">
                <h2 class="h6 mb-1">Podaci za plaće i prava (čl. 3. st. 2.)</h2>
                <p class="small text-muted mb-0">Unos, ne obračun. IBAN i koeficijent idu u izvoz za računovodstvo.</p>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="iban">IBAN</label>
                <input class="form-control" name="iban" id="iban" value="{{ old('iban', $person?->iban) }}" maxlength="34" placeholder="HR12…" autocomplete="off">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="pay_coefficient">Koeficijent</label>
                <input type="number" step="0.0001" min="0" max="99.9999" class="form-control" name="pay_coefficient" id="pay_coefficient" value="{{ old('pay_coefficient', $person?->pay_coefficient) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="allowance_percent">Dodaci %</label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="allowance_percent" id="allowance_percent" value="{{ old('allowance_percent', $person?->allowance_percent) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="prior_service_months">Staž prije (mj.)</label>
                <input type="number" min="0" max="720" class="form-control" name="prior_service_months" id="prior_service_months" value="{{ old('prior_service_months', $person?->prior_service_months) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="children_count">Djeca (GO)</label>
                <input type="number" min="0" max="20" class="form-control" name="children_count" id="children_count" value="{{ old('children_count', $person?->children_count ?? 0) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="dependents_count">Uzdržavani</label>
                <input type="number" min="0" max="20" class="form-control" name="dependents_count" id="dependents_count" value="{{ old('dependents_count', $person?->dependents_count ?? 0) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tax_relief_note">Olakšica / napomena</label>
                <input class="form-control" name="tax_relief_note" id="tax_relief_note" value="{{ old('tax_relief_note', $person?->tax_relief_note) }}" maxlength="255" placeholder="npr. 1 dijete, invaliditet">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="family_right">Rodiljna / roditeljska</label>
                <select class="form-select" name="family_right" id="family_right">
                    <option value="">nije aktivno</option>
                    @foreach($familyRights as $right)
                        <option value="{{ $right->value }}" @selected(old('family_right', $person?->family_right?->value) === $right->value)>{{ $right->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="znr_exam_required" id="znr_exam_required" value="1" @checked(old('znr_exam_required', $person?->znr_exam_required))>
                    <label class="form-check-label" for="znr_exam_required">Obavezan ZNR pregled</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-primary" type="submit">Spremi</button>
        @if($person)
            <a class="btn btn-outline-primary" href="{{ route('organization.people.review', [$organization->slug, $person]) }}">Pisani pregled</a>
            <a class="btn btn-outline-primary" href="{{ route('organization.people.contract', [$organization->slug, $person]) }}">UOR</a>
            <a class="btn btn-outline-primary" href="{{ route('organization.people.referral', [$organization->slug, $person]) }}">Uputnica</a>
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('organization.people.index', $organization->slug) }}">Odustani</a>
    </div>
</form>
@if($person)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">Ugovori i aneksi</div>
        <div class="card-body">
            <div class="table-responsive mb-4">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Vrsta</th>
                            <th>Broj</th>
                            <th>Trajanje</th>
                            <th>Probni</th>
                            <th>Sati/tj.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($person->employmentContracts as $item)
                            <tr>
                                <td>{{ $item->kind->label() }}{{ $item->is_current ? ' · važeći' : '' }}{{ $item->contract_type ? ' · '.$item->contract_type->label() : '' }}</td>
                                <td>{{ $item->number ?: '—' }}</td>
                                <td>{{ $item->starts_at->format('d.m.Y.') }}{{ $item->ends_at ? ' – '.$item->ends_at->format('d.m.Y.') : '' }}</td>
                                <td>{{ $item->trial_ends_at?->format('d.m.Y.') ?: '—' }}</td>
                                <td>{{ $item->weekly_hours ?: '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('organization.contracts.destroy', [$organization->slug, $person, $item]) }}" onsubmit="return confirm('Ukloniti ovaj ugovor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Još nema ugovora. Ispis UOR-a i istek određenog čitaju važeći slog.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('organization.contracts.store', [$organization->slug, $person]) }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="instrument_kind">Isprava</label>
                    <select class="form-select" name="kind" id="instrument_kind" required>
                        @foreach($instrumentKinds as $kind)
                            <option value="{{ $kind->value }}" @selected(old('kind', 'uor') === $kind->value)>{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_contract_type">Vrsta</label>
                    <select class="form-select" name="contract_type" id="instrument_contract_type">
                        <option value="">—</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->value }}" @selected(old('contract_type', $person->contract_type?->value) === $contract->value)>{{ $contract->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_number">Broj</label>
                    <input class="form-control" name="number" id="instrument_number" value="{{ old('number') }}" maxlength="64" placeholder="auto ako prazno">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_weekly_hours">Sati tjedno</label>
                    <input type="number" min="1" max="60" class="form-control" name="weekly_hours" id="instrument_weekly_hours" value="{{ old('weekly_hours', 40) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_signed_at">Potpis</label>
                    <input type="date" class="form-control" name="signed_at" id="instrument_signed_at" value="{{ old('signed_at', $person->started_at?->toDateString() ?? now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_starts_at">Početak</label>
                    <input type="date" class="form-control" name="starts_at" id="instrument_starts_at" value="{{ old('starts_at', $person->started_at?->toDateString() ?? now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_ends_at">Istek (određeno)</label>
                    <input type="date" class="form-control" name="ends_at" id="instrument_ends_at" value="{{ old('ends_at') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="instrument_trial_ends_at">Probni rad do</label>
                    <input type="date" class="form-control" name="trial_ends_at" id="instrument_trial_ends_at" value="{{ old('trial_ends_at') }}">
                </div>
                <div class="col-md-9">
                    <label class="form-label" for="instrument_note">Napomena</label>
                    <input class="form-control" name="note" id="instrument_note" value="{{ old('note') }}" maxlength="255">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_current" id="instrument_is_current" value="1" @checked(old('is_current', '1'))>
                        <label class="form-check-label" for="instrument_is_current">Važeći</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-outline-primary" type="submit">Dodaj ugovor</button>
                </div>
            </form>
        </div>
    </div>
@endif
@if($person)
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">Obrazovanje i certifikati (čl. 3. st. 1. t. 8.)</div>
        <div class="card-body">
            <div class="table-responsive mb-4">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Vrsta</th>
                            <th>Naziv</th>
                            <th>Izdavatelj</th>
                            <th>Izdano</th>
                            <th>Istek</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($person->qualifications as $item)
                            <tr>
                                <td>{{ $item->kind->label() }}@if($item->required_for_job) <span class="badge text-bg-secondary">uvjet</span>@endif</td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->issuer ?: '—' }}</td>
                                <td>{{ $item->issued_on?->format('d.m.Y.') ?: '—' }}</td>
                                <td>{{ $item->expires_at?->format('d.m.Y.') ?: '—' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('organization.qualifications.destroy', [$organization->slug, $person, $item]) }}" onsubmit="return confirm('Ukloniti ovu stavku?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">Još nema unosa. To je točka 8. pisanog pregleda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('organization.qualifications.store', [$organization->slug, $person]) }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="kind">Vrsta</label>
                    <select class="form-select" name="kind" id="kind" required>
                        @foreach($qualificationKinds as $kind)
                            <option value="{{ $kind->value }}" @selected(old('kind') === $kind->value)>{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="title">Naziv</label>
                    <input class="form-control" name="title" id="title" value="{{ old('title') }}" required maxlength="255" placeholder="npr. Mag. oec. / Viljuškar">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="issuer">Izdavatelj</label>
                    <input class="form-control" name="issuer" id="issuer" value="{{ old('issuer') }}" maxlength="255">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="issued_on">Izdano</label>
                    <input type="date" class="form-control" name="issued_on" id="issued_on" value="{{ old('issued_on') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="expires_at">Istek</label>
                    <input type="date" class="form-control" name="expires_at" id="expires_at" value="{{ old('expires_at') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="note">Napomena</label>
                    <input class="form-control" name="note" id="note" value="{{ old('note') }}" maxlength="255">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="required_for_job" id="required_for_job" value="1" @checked(old('required_for_job'))>
                        <label class="form-check-label" for="required_for_job">Uvjet za posao</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-outline-primary" type="submit">Dodaj kvalifikaciju</button>
                </div>
            </form>
        </div>
    </div>
@endif
@if($person && $person->status === \App\Enums\PersonStatus::Candidate)
    <form method="POST" action="{{ route('organization.people.hire', [$organization->slug, $person]) }}" class="mt-3">
        @csrf
        <button class="btn btn-outline-success" type="submit">Prenesi u kadar</button>
    </form>
@endif
@endsection
