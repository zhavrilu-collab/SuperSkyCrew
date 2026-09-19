@extends('layouts.print')

@section('title', 'Pisani pregled — '.$person->fullName())

@section('toolbar')
    @if(app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
        <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="small">← Kartica</a>
    @else
        <a href="{{ route('organization.dashboard', $organization->slug) }}" class="small">← Ploča</a>
    @endif
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
        @if(app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
            <a class="btn btn-outline-primary" href="{{ route('organization.people.contract', [$organization->slug, $person]) }}">UOR</a>
            <a class="btn btn-outline-primary" href="{{ route('organization.people.referral', [$organization->slug, $person]) }}">Uputnica</a>
        @elseif((int) $person->user_id === (int) auth()->id())
            <a class="btn btn-outline-primary" href="{{ route('organization.people.contract', [$organization->slug, $person]) }}">Ugovor o radu</a>
        @endif
        @if((int) $person->user_id === (int) auth()->id())
            <a class="btn btn-outline-primary" href="{{ route('organization.requests.create', [$organization->slug, 'type' => 'personal_data']) }}">Prijavi promjenu</a>
        @endif
    </div>
@endsection

@section('content')
    <h1 class="h4 mb-1">Pisani pregled evidencije o radniku</h1>
    <p class="text-muted">Pravilnik NN 55/2024, čl. 4. u vezi s čl. 3. st. 1.</p>

    <dl class="row">
        <dt class="col-sm-3">Poslodavac</dt>
        <dd class="col-sm-9">{{ $organization->name }}@if($organization->oib) · OIB {{ $organization->oib }}@endif</dd>
        <dt class="col-sm-3">Izradio</dt>
        <dd class="col-sm-9">{{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</dd>
        <dt class="col-sm-3">Status u evidenciji</dt>
        <dd class="col-sm-9">{{ $person->engagementLabel() }}</dd>
        @if($person->status === \App\Enums\PersonStatus::Assigned)
            <dt class="col-sm-3">Ustupitelj / agencija</dt>
            <dd class="col-sm-9">{{ $person->host_employer ?: '—' }}{{ $person->assignment_clocks ? ' · evidencija RV ugovorena' : ' · evidencija RV nije ugovorena' }}</dd>
        @endif
        @if($person->status === \App\Enums\PersonStatus::Executive && $person->executive_autonomy)
            <dt class="col-sm-3">Čl. 21.</dt>
            <dd class="col-sm-9">Ugovorena samostalnost — smanjeni slog (bez upozorenja dnevnog odmora, kašnjenja i mjesečnog fonda).</dd>
        @endif
    </dl>

    <ol class="list-group list-group-numbered mb-4">
        <li class="list-group-item d-flex justify-content-between"><span>Ime i prezime</span><strong>{{ $person->fullName() }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>OIB</span><strong>{{ $person->oib ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Spol</span><strong>{{ $person->genderLabel() }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Datum rođenja</span><strong>{{ $person->date_of_birth?->format('d.m.Y.') ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Državljanstvo</span><strong>{{ $person->citizenship ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Prebivalište / boravište</span><strong>{{ $person->residence ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Dozvola za boravak i rad</span><strong>{{ $person->work_permit_expires_at ? 'vrijedi do '.$person->work_permit_expires_at->format('d.m.Y.') : '—' }}</strong></li>
        <li class="list-group-item">
            <div class="d-flex justify-content-between">
                <span>Stručno obrazovanje / certifikati</span>
                <strong>{{ $person->qualifications->isEmpty() ? 'nije uneseno u karticu' : '' }}</strong>
            </div>
            @if($person->qualifications->isNotEmpty())
                <ul class="mb-0 mt-2 ps-3">
                    @foreach($person->qualifications as $item)
                        <li>{{ $item->summary() }}{{ $item->required_for_job ? ' · uvjet za posao' : '' }}</li>
                    @endforeach
                </ul>
            @endif
        </li>
        <li class="list-group-item d-flex justify-content-between"><span>Datum početka rada</span><strong>{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Naziv radnog mjesta / vrsta rada</span><strong>{{ $person->jobPosition?->summary() ?: ($person->job_title ?: '—') }}{{ $person->location ? ' · '.$person->location->name : '' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Odjel</span><strong>{{ $person->department?->name ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Mjesto troška</span><strong>{{ $person->costCenter?->summary() ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Vrsta ugovora o radu</span><strong>{{ $person->currentContract()?->contract_type?->label() ?: ($person->contract_type?->label() ?: '—') }}</strong></li>
        <li class="list-group-item">
            <div class="d-flex justify-content-between">
                <span>Ugovori i aneksi</span>
                <strong>{{ $person->employmentContracts->isEmpty() ? 'nema ugovora u dosjeu' : '' }}</strong>
            </div>
            @if($person->employmentContracts->isNotEmpty())
                <ul class="mb-0 mt-2 ps-3">
                    @foreach($person->employmentContracts as $item)
                        <li>{{ $item->summary() }}{{ $item->is_current ? ' · važeći' : '' }}</li>
                    @endforeach
                </ul>
            @endif
        </li>
        <li class="list-group-item d-flex justify-content-between"><span>Datum i razlog prestanka</span><strong>{{ $person->ended_at ? $person->ended_at->format('d.m.Y.').($person->ended_reason ? ' · '.$person->ended_reason : '') : '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Prijava / promjena na obvezna osiguranja</span><strong>{{ $person->insurance_filed_at?->format('d.m.Y.') ?: '—' }}</strong></li>
    </ol>

    <h2 class="h6">Ostali podaci od kojih ovise prava (čl. 3. st. 2.)</h2>
    <dl class="row">
        <dt class="col-sm-4">IBAN</dt>
        <dd class="col-sm-8">{{ $person->iban ?: '—' }}</dd>
        <dt class="col-sm-4">Koeficijent / dodaci</dt>
        <dd class="col-sm-8">{{ $person->pay_coefficient !== null ? $person->pay_coefficient : '—' }}{{ $person->allowance_percent !== null ? ' · dodaci '.$person->allowance_percent.' %' : '' }}</dd>
        <dt class="col-sm-4">Staž prije ovog poslodavca</dt>
        <dd class="col-sm-8">{{ $person->priorServiceLabel() }}</dd>
        <dt class="col-sm-4">Djeca (GO) / uzdržavani</dt>
        <dd class="col-sm-8">{{ $person->children_count }} / {{ $person->dependents_count }}</dd>
        <dt class="col-sm-4">Olakšica</dt>
        <dd class="col-sm-8">{{ $person->tax_relief_note ?: '—' }}</dd>
        <dt class="col-sm-4">Rodiljna / roditeljska</dt>
        <dd class="col-sm-8">{{ $person->family_right?->label() ?: '—' }}</dd>
        <dt class="col-sm-4">Liječnički pregled</dt>
        <dd class="col-sm-8">{{ $person->medical_expires_at?->format('d.m.Y.') ?: '—' }}{{ $person->znr_exam_required ? ' · ZNR obavezan' : '' }}</dd>
        <dt class="col-sm-4">Certifikat / atest</dt>
        <dd class="col-sm-8">{{ $person->certificate_expires_at?->format('d.m.Y.') ?: '—' }}</dd>
    </dl>

    <p class="small text-muted mb-0">Radnik ima pravo uvida u vlastite podatke (čl. 5. st. 1.). Ovaj ispis nije preslika osobne iskaznice.</p>
@endsection
