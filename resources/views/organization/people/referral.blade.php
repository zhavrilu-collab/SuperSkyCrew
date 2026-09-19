@extends('layouts.print')

@section('title', 'Uputnica za liječnički pregled '.$number)

@section('toolbar')
    <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="small">← Kartica</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <p class="text-end mb-1">Klasa: {{ $number }}<br>
        {{ $issuedAt->timezone(config('app.timezone'))->format('d.m.Y.') }}</p>

    <h1 class="h4 text-center mt-3 mb-4">UPUTNICA<br><span class="fs-6 fw-normal">za liječnički pregled radnika</span></h1>

    <p>Poslodavac <strong>{{ $organization->name }}</strong> upućuje radnika na liječnički pregled radi utvrđivanja sposobnosti za obavljanje poslova, u skladu s propisima o zaštiti na radu.</p>

    <dl class="row">
        <dt class="col-sm-4">Ime i prezime</dt>
        <dd class="col-sm-8">{{ $person->fullName() }}</dd>
        <dt class="col-sm-4">OIB</dt>
        <dd class="col-sm-8">{{ $person->oib ?: '—' }}</dd>
        <dt class="col-sm-4">Datum rođenja</dt>
        <dd class="col-sm-8">{{ $person->date_of_birth?->format('d.m.Y.') ?: '—' }}</dd>
        <dt class="col-sm-4">Radno mjesto</dt>
        <dd class="col-sm-8">{{ $person->jobPosition?->summary() ?: ($person->job_title ?: '—') }}{{ $person->department ? ' · '.$person->department->name : '' }}{{ $person->location ? ' · '.$person->location->name : '' }}</dd>
        <dt class="col-sm-4">Početak rada</dt>
        <dd class="col-sm-8">{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</dd>
        <dt class="col-sm-4">Važeći pregled do</dt>
        <dd class="col-sm-8">{{ $person->medical_expires_at?->format('d.m.Y.') ?: 'nije unesen / prvi pregled' }}</dd>
    </dl>

    @if($person->qualifications->isNotEmpty())
        <p class="mb-1">Kvalifikacije / uvjeti za posao s kartice:</p>
        <ul>
            @foreach($person->qualifications as $item)
                <li>{{ $item->summary() }}{{ $item->required_for_job ? ' · uvjet za posao' : '' }}</li>
            @endforeach
        </ul>
    @endif

    <p>Molimo ovlaštenog liječnika medicine rada da obavi pregled i izda potvrdu o zdravstvenoj sposobnosti za navedene poslove.</p>

    <p class="small text-muted">Uputnicu je izradio {{ $issuer->name }}. Nakon pregleda unesite novi datum isteka na kartici radnika.</p>

    <div class="row mt-5">
        <div class="col-6">
            <div class="small text-muted">Radnik (preuzeo)</div>
            <div class="sign border-bottom">&nbsp;</div>
            <div class="small">{{ $person->fullName() }}</div>
        </div>
        <div class="col-6 text-end">
            <div class="small text-muted">Za poslodavca</div>
            <div class="sign border-bottom">&nbsp;</div>
            <div class="small">{{ $issuer->name }}</div>
        </div>
    </div>
@endsection
