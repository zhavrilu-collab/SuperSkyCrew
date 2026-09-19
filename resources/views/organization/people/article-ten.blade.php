@extends('layouts.print')

@section('title', 'Evidencija FO — '.$person->fullName())

@section('toolbar')
    @if(app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
        <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="small">← Kartica</a>
    @else
        <a href="{{ route('organization.dashboard', $organization->slug) }}" class="small">← Ploča</a>
    @endif
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <h1 class="h4 mb-1">Pisani pregled evidencije o drugim fizičkim osobama</h1>
    <p class="text-muted">Pravilnik NN 55/2024, čl. 10. st. 2.</p>

    <dl class="row">
        <dt class="col-sm-3">Naručitelj / organizator</dt>
        <dd class="col-sm-9">{{ $organization->name }}{{ $organization->oib ? ' · OIB '.$organization->oib : '' }}</dd>
        <dt class="col-sm-3">Izradio</dt>
        <dd class="col-sm-9">{{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</dd>
        <dt class="col-sm-3">Vrsta osobe</dt>
        <dd class="col-sm-9">{{ $person->engagementLabel() }}</dd>
    </dl>

    <ol class="list-group list-group-numbered mb-4">
        <li class="list-group-item d-flex justify-content-between"><span>Ime i prezime</span><strong>{{ $person->fullName() }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>OIB</span><strong>{{ $person->oib ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Spol</span><strong>{{ $person->genderLabel() }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Datum rođenja</span><strong>{{ $person->date_of_birth?->format('d.m.Y.') ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Državljanstvo</span><strong>{{ $person->citizenship ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Prebivalište / boravište</span><strong>{{ $person->residence ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Naziv ugovora / akta</span><strong>{{ $person->instrument_title ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Mjesto rada</span><strong>{{ $person->location?->name ?: '—' }}{{ $person->jobLabel() !== '—' ? ' · '.$person->jobLabel() : '' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Početak rada</span><strong>{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Prestanak rada</span><strong>{{ $person->ended_at ? $person->ended_at->format('d.m.Y.').($person->ended_reason ? ' · '.$person->ended_reason : '') : '—' }}</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span>Prijava na osiguranja</span><strong>{{ $person->insurance_filed_at?->format('d.m.Y.') ?: 'nije unesena' }}</strong></li>
    </ol>

    <p class="small text-muted mb-0">Ova evidencija nije kartica radnika na ugovoru o radu (čl. 3.). Trajanje rada vodi se u šihterici (čl. 22.). Osoba ima pravo uvida u vlastite podatke (čl. 5. st. 1.).</p>
@endsection
