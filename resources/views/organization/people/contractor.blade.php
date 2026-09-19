@extends('layouts.print')

@section('title', 'Kartica honorarca — '.$person->fullName())

@section('toolbar')
    @if(app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
        <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="small">← Kartica</a>
    @else
        <a href="{{ route('organization.dashboard', $organization->slug) }}" class="small">← Ploča</a>
    @endif
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <h1 class="h4 mb-1">Kartica honorarca</h1>
    <p class="text-muted">Interna evidencija UOD / autorskog ugovora. Nije evidencija radnika (čl. 3.) ni evidencija drugih FO (čl. 10.).</p>

    <dl class="row">
        <dt class="col-sm-3">Naručitelj</dt>
        <dd class="col-sm-9">{{ $organization->name }}{{ $organization->oib ? ' · OIB '.$organization->oib : '' }}</dd>
        <dt class="col-sm-3">Izradio</dt>
        <dd class="col-sm-9">{{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</dd>
        <dt class="col-sm-3">Vrsta osobe</dt>
        <dd class="col-sm-9">{{ $person->engagementLabel() }}</dd>
        <dt class="col-sm-3">Ime i prezime</dt>
        <dd class="col-sm-9">{{ $person->fullName() }}</dd>
        <dt class="col-sm-3">OIB</dt>
        <dd class="col-sm-9">{{ $person->oib ?: '—' }}</dd>
        <dt class="col-sm-3">Naziv ugovora / akta</dt>
        <dd class="col-sm-9">{{ $person->instrument_title ?: '—' }}</dd>
        <dt class="col-sm-3">Mjesto rada</dt>
        <dd class="col-sm-9">{{ $person->location?->name ?: '—' }}{{ $person->jobLabel() !== '—' ? ' · '.$person->jobLabel() : '' }}</dd>
        <dt class="col-sm-3">Početak</dt>
        <dd class="col-sm-9">{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</dd>
        <dt class="col-sm-3">Prestanak</dt>
        <dd class="col-sm-9">{{ $person->ended_at ? $person->ended_at->format('d.m.Y.').($person->ended_reason ? ' · '.$person->ended_reason : '') : '—' }}</dd>
    </dl>

    <p class="small text-muted mb-0">Trajanje rada vodi se u šihterici. Osoba ima pravo uvida u vlastite podatke (čl. 5. st. 1.).</p>
@endsection
