<div class="{{ $profileTab === 'osobno' ? '' : 'd-none' }}">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Osobni podaci</h2>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="first_name">Ime</label>
            <input class="form-control" name="first_name" id="first_name" value="{{ old('first_name', $person->first_name) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="last_name">Prezime</label>
            <input class="form-control" name="last_name" id="last_name" value="{{ old('last_name', $person->last_name) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="oib">OIB</label>
            <input class="form-control" name="oib" id="oib" value="{{ old('oib', $person->oib) }}" maxlength="11">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="gender">Spol</label>
            <select class="form-select" name="gender" id="gender">
                <option value="">—</option>
                <option value="m" @selected(old('gender', $person->gender) === 'm')>M</option>
                <option value="z" @selected(old('gender', $person->gender) === 'z')>Ž</option>
                <option value="x" @selected(old('gender', $person->gender) === 'x')>Ostalo</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="date_of_birth">Datum rođenja</label>
            <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $person->date_of_birth?->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="citizenship">Državljanstvo</label>
            <input class="form-control" name="citizenship" id="citizenship" value="{{ old('citizenship', $person->citizenship) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="residence">Prebivalište / boravište</label>
            <input class="form-control" name="residence" id="residence" value="{{ old('residence', $person->residence) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $person->email) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="phone">Telefon</label>
            <input class="form-control" name="phone" id="phone" value="{{ old('phone', $person->phone) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="user_id">Korisnički račun</label>
            <select class="form-select" name="user_id" id="user_id">
                <option value="">nije povezano</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('user_id', $person->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="{{ $profileTab === 'zaposlenje' ? '' : 'd-none' }}">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Zaposlenje</h2>
    </div>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" name="status" id="status" required>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $person->status?->value ?? 'employee') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="department_id">Odjel</label>
            <select class="form-select" name="department_id" id="department_id">
                <option value="">—</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('department_id', $person->department_id) === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="legal_entity_id">Pravna osoba</label>
            <select class="form-select" name="legal_entity_id" id="legal_entity_id">
                <option value="">—</option>
                @foreach($legalEntities as $legalEntity)
                    <option value="{{ $legalEntity->id }}" @selected((string) old('legal_entity_id', $person->legal_entity_id) === (string) $legalEntity->id)>{{ $legalEntity->summary() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="work_center_id">Poslovnica</label>
            <select class="form-select" name="work_center_id" id="work_center_id">
                <option value="">—</option>
                @foreach($workCenters as $workCenter)
                    <option value="{{ $workCenter->id }}" @selected((string) old('work_center_id', $person->work_center_id) === (string) $workCenter->id)>{{ $workCenter->summary() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="job_position_id">Radno mjesto (šifrarnik)</label>
            <select class="form-select" name="job_position_id" id="job_position_id">
                <option value="">—</option>
                @foreach($positions as $position)
                    <option value="{{ $position->id }}" @selected((string) old('job_position_id', $person->job_position_id) === (string) $position->id)>{{ $position->summary() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="org_position_id">Radna pozicija (stolica)</label>
            <select class="form-select" name="org_position_id" id="org_position_id">
                <option value="">—</option>
                @foreach($orgSeats ?? [] as $seat)
                    @if(! $seat->person_id || (int) $seat->person_id === (int) $person->id)
                        <option value="{{ $seat->id }}" @selected((string) old('org_position_id', $person->org_position_id) === (string) $seat->id)>{{ $seat->label() }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="cost_center_id">Mjesto troška</label>
            <select class="form-select" name="cost_center_id" id="cost_center_id">
                <option value="">—</option>
                @foreach($costCenters as $costCenter)
                    <option value="{{ $costCenter->id }}" @selected((string) old('cost_center_id', $person->cost_center_id) === (string) $costCenter->id)>{{ $costCenter->summary() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="job_title">Naziv posla</label>
            <input class="form-control" name="job_title" id="job_title" value="{{ old('job_title', $person->job_title) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="contract_type">Vrsta ugovora</label>
            <select class="form-select" name="contract_type" id="contract_type">
                <option value="">—</option>
                @foreach($contracts as $contract)
                    <option value="{{ $contract->value }}" @selected(old('contract_type', $person->contract_type?->value) === $contract->value)>{{ $contract->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="started_at">Početak rada</label>
            <input type="date" class="form-control" name="started_at" id="started_at" value="{{ old('started_at', $person->started_at?->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="ended_at">Prestanak</label>
            <input type="date" class="form-control" name="ended_at" id="ended_at" value="{{ old('ended_at', $person->ended_at?->toDateString()) }}">
        </div>
        @if(! empty($serviceCard))
            <div class="col-12">
                <p class="small text-muted mb-0">Staž kod poslodavca: {{ intdiv($serviceCard['employer_months'], 12) }} g. {{ $serviceCard['employer_months'] % 12 }} mj. · prije: {{ $serviceCard['prior_months'] }} mj. · ukupno {{ $serviceCard['total_label'] }}@if($serviceCard['retirement_date']) · mirovina (65): {{ $serviceCard['retirement_date'] }} ({{ $serviceCard['retirement_years'] }} g.)@endif</p>
            </div>
        @endif
        <div class="col-md-6">
            <label class="form-label" for="ended_reason">Razlog prestanka</label>
            <input class="form-control" name="ended_reason" id="ended_reason" value="{{ old('ended_reason', $person->ended_reason) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="insurance_filed_at">Prijava na osiguranja</label>
            <input type="date" class="form-control" name="insurance_filed_at" id="insurance_filed_at" value="{{ old('insurance_filed_at', $person->insurance_filed_at?->toDateString()) }}">
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
                    <option value="{{ $kind->value }}" @selected(old('fo_kind', $person->fo_kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label" for="instrument_title">Naziv ugovora / akta</label>
            <input class="form-control" name="instrument_title" id="instrument_title" value="{{ old('instrument_title', $person->instrument_title) }}" maxlength="255" placeholder="npr. Ugovor o studentskom poslu / Ugovor o djelu">
        </div>
        <div class="col-12">
            <div class="forma-sekcija">
                <h2>Ustupljeni i rukovodeće</h2>
                <p>Ustupljeni (agencija / povezano društvo) ulaze u pisani pregled čl. 4. Rukovodeća s ugovorenom samostalnošću ima smanjeni slog RV (čl. 21.). Volonter nije u matičnoj knjizi ni na šihterici.</p>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="host_employer">Ustupitelj / agencija</label>
            <input class="form-control" name="host_employer" id="host_employer" value="{{ old('host_employer', $person->host_employer) }}" maxlength="255" placeholder="obavezno za ustupljenog radnika">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="assignment_clocks" id="assignment_clocks" value="1" @checked(old('assignment_clocks', $person->assignment_clocks ?? true))>
                <label class="form-check-label" for="assignment_clocks">Evidencija RV ugovorena</label>
            </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="executive_autonomy" id="executive_autonomy" value="1" @checked(old('executive_autonomy', $person->executive_autonomy))>
                <label class="form-check-label" for="executive_autonomy">Samostalnost (čl. 21.)</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="work_permit_expires_at">Istek dozvole</label>
            <input type="date" class="form-control" name="work_permit_expires_at" id="work_permit_expires_at" value="{{ old('work_permit_expires_at', $person->work_permit_expires_at?->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="medical_expires_at">Istek liječničkog</label>
            <input type="date" class="form-control" name="medical_expires_at" id="medical_expires_at" value="{{ old('medical_expires_at', $person->medical_expires_at?->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="certificate_expires_at">Istek certifikata</label>
            <input type="date" class="form-control" name="certificate_expires_at" id="certificate_expires_at" value="{{ old('certificate_expires_at', $person->certificate_expires_at?->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="location_id">Lokacija</label>
            <select class="form-select" name="location_id" id="location_id">
                <option value="">—</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected((string) old('location_id', $person->location_id) === (string) $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="manager_user_id">Voditelj</label>
            <select class="form-select" name="manager_user_id" id="manager_user_id">
                <option value="">—</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected((string) old('manager_user_id', $person->manager_user_id) === (string) $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="dotted_manager_user_id">Dotted line</label>
            <select class="form-select" name="dotted_manager_user_id" id="dotted_manager_user_id">
                <option value="">—</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected((string) old('dotted_manager_user_id', $person->dotted_manager_user_id) === (string) $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="annual_leave_days">Fond GO (dana)</label>
            <input type="number" min="0" max="50" class="form-control" name="annual_leave_days" id="annual_leave_days" value="{{ old('annual_leave_days', $person->annual_leave_days ?? ($leaveSnapshot['calculated'] ?? 20)) }}">
            @if(! empty($leaveSnapshot))
                <p class="form-text mb-1">
                    Politika {{ $leaveSnapshot['year'] }}.: max({{ $leaveSnapshot['parts']['base'] }}, RM {{ $leaveSnapshot['parts']['position'] ?: '—' }})
                    + staž {{ $leaveSnapshot['parts']['tenure_years'] }} g. (+{{ $leaveSnapshot['parts']['tenure_extra'] }})
                    + djeca {{ $leaveSnapshot['parts']['children'] }} (+{{ $leaveSnapshot['parts']['children_extra'] }})
                    = {{ $leaveSnapshot['calculated'] }}.
                    Preostalo {{ $leaveSnapshot['remaining'] }} (staro {{ $leaveSnapshot['remaining_old'] }} / novo {{ $leaveSnapshot['remaining_new'] }}).
                </p>
            @endif
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="annual_leave_manual" id="annual_leave_manual" value="1" @checked(old('annual_leave_manual', $person->annual_leave_manual))>
                <label class="form-check-label" for="annual_leave_manual">Ručno (ne preračunavaj)</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="clock_pin">PIN za kiosk</label>
            <input class="form-control" name="clock_pin" id="clock_pin" value="{{ old('clock_pin', $person->clock_pin) }}" maxlength="6" inputmode="numeric">
            @if($person->exists && $person->isClockEligible())
                <p class="form-text mb-1">QR iskaznica za kiosk: <a href="{{ route('organization.people.badge', [$organization->slug, $person]) }}">ispiši</a></p>
                <form method="POST" action="{{ route('organization.people.clock-token.rotate', [$organization->slug, $person]) }}" class="mt-1">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Obnovi Clock API token</button>
                </form>
            @endif
            @if($person->clock_device_id)
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="clock_device_reset" id="clock_device_reset" value="1">
                    <label class="form-check-label" for="clock_device_reset">Poništi vezani PWA uređaj</label>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="{{ $profileTab === 'place' ? '' : 'd-none' }}">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Podaci za plaće i prava (čl. 3. st. 2.)</h2>
        <p>Unos, ne obračun. IBAN i koeficijent idu u izvoz za računovodstvo.</p>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="iban">IBAN</label>
            <input class="form-control" name="iban" id="iban" value="{{ old('iban', $person->iban) }}" maxlength="34" placeholder="HR12…" autocomplete="off">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pay_coefficient">Koeficijent</label>
            <input type="number" step="0.0001" min="0" max="99.9999" class="form-control" name="pay_coefficient" id="pay_coefficient" value="{{ old('pay_coefficient', $person->pay_coefficient) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="allowance_percent">Dodaci %</label>
            <input type="number" step="0.01" min="0" max="100" class="form-control" name="allowance_percent" id="allowance_percent" value="{{ old('allowance_percent', $person->allowance_percent) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="prior_service_months">Staž prije (mj.)</label>
            <input type="number" min="0" max="720" class="form-control" name="prior_service_months" id="prior_service_months" value="{{ old('prior_service_months', $person->prior_service_months) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="children_count">Djeca (GO)</label>
            <input type="number" min="0" max="20" class="form-control" name="children_count" id="children_count" value="{{ old('children_count', $person->children_count ?? 0) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="dependents_count">Uzdržavani</label>
            <input type="number" min="0" max="20" class="form-control" name="dependents_count" id="dependents_count" value="{{ old('dependents_count', $person->dependents_count ?? 0) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="tax_relief_note">Olakšica / napomena</label>
            <input class="form-control" name="tax_relief_note" id="tax_relief_note" value="{{ old('tax_relief_note', $person->tax_relief_note) }}" maxlength="255" placeholder="npr. 1 dijete, invaliditet">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="family_right">Rodiljna / roditeljska</label>
            <select class="form-select" name="family_right" id="family_right">
                <option value="">nije aktivno</option>
                @foreach($familyRights as $right)
                    <option value="{{ $right->value }}" @selected(old('family_right', $person->family_right?->value) === $right->value)>{{ $right->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="znr_exam_required" id="znr_exam_required" value="1" @checked(old('znr_exam_required', $person->znr_exam_required))>
                <label class="form-check-label" for="znr_exam_required">Obavezan ZNR pregled</label>
            </div>
        </div>
    </div>
</div>
