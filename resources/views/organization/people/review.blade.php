<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pisani pregled — {{ $person->fullName() }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            a { text-decoration: none; color: inherit; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2 no-print">
        @if(app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
            <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="small">← Kartica</a>
        @else
            <a href="{{ route('organization.dashboard', $organization->slug) }}" class="small">← Ploča</a>
        @endif
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

    <h1 class="h4 mb-1">Pisani pregled evidencije o radniku</h1>
    <p class="text-muted">Pravilnik NN 55/2024, čl. 4. u vezi s čl. 3. st. 1.</p>

    <dl class="row">
        <dt class="col-sm-3">Poslodavac</dt>
        <dd class="col-sm-9">{{ $organization->name }}@if($organization->oib) · OIB {{ $organization->oib }}@endif</dd>
        <dt class="col-sm-3">Izradio</dt>
        <dd class="col-sm-9">{{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</dd>
        <dt class="col-sm-3">Status u evidenciji</dt>
        <dd class="col-sm-9">{{ $person->status->label() }}</dd>
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
                @if($person->qualifications->isEmpty())
                    <strong>nije uneseno u karticu</strong>
                @endif
            </div>
            @if($person->qualifications->isNotEmpty())
                <ul class="mb-0 mt-2 ps-3">
                    @foreach($person->qualifications as $item)
                        <li>
                            {{ $item->summary() }}
                            @if($item->required_for_job)
                                · uvjet za posao
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
        <li class="list-group-item d-flex justify-content-between"><span>Datum početka rada</span><strong>{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Naziv radnog mjesta / vrsta rada</span><strong>{{ $person->jobPosition?->summary() ?: ($person->job_title ?: '—') }}{{ $person->location ? ' · '.$person->location->name : '' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Odjel</span><strong>{{ $person->department?->name ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Mjesto troška</span><strong>{{ $person->costCenter?->summary() ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Vrsta ugovora o radu</span><strong>{{ $person->contract_type?->label() ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Datum i razlog prestanka</span><strong>{{ $person->ended_at ? $person->ended_at->format('d.m.Y.').($person->ended_reason ? ' · '.$person->ended_reason : '') : '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Prijava / promjena na obvezna osiguranja</span><strong>{{ $person->insurance_filed_at?->format('d.m.Y.') ?: '—' }}</strong></li>
    </ol>

    <h2 class="h6">Ostali rokovi (čl. 3. st. 2.)</h2>
    <dl class="row">
        <dt class="col-sm-4">Liječnički pregled</dt>
        <dd class="col-sm-8">{{ $person->medical_expires_at?->format('d.m.Y.') ?: '—' }}</dd>
        <dt class="col-sm-4">Certifikat / atest</dt>
        <dd class="col-sm-8">{{ $person->certificate_expires_at?->format('d.m.Y.') ?: '—' }}</dd>
    </dl>

    <p class="small text-muted mb-0">Radnik ima pravo uvida u vlastite podatke (čl. 5. st. 1.). Ovaj ispis nije preslika osobne iskaznice.</p>
</div>
</body>
</html>
