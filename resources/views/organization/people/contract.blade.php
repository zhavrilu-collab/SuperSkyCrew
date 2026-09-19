@extends('layouts.print')

@section('title', 'Ugovor o radu '.$number)

@section('toolbar')
    @if(app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'people.access'))
        <a href="{{ route('organization.people.edit', [$organization->slug, $person]) }}" class="small">← Kartica</a>
    @else
        <a href="{{ route('organization.people.review', [$organization->slug, $person]) }}" class="small">← Pisani pregled</a>
    @endif
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <p class="text-end mb-1">Klasa: {{ $number }}<br>
        {{ $issuedAt->timezone(config('app.timezone'))->format('d.m.Y.') }}@if($organization->city) · {{ $organization->city }}@endif</p>

    <h1 class="h4 text-center mt-3 mb-4">{{ $printTitle }}</h1>

    <p>sklopljen između:</p>
    <p><strong>Poslodavac:</strong> {{ $organization->name }}@if($organization->oib), OIB {{ $organization->oib }}@endif
        @if($organization->city), {{ $organization->city }}@endif
        @if($organization->email), {{ $organization->email }}@endif
        (dalje: poslodavac)</p>
    <p>i</p>
    <p><strong>Radnik:</strong> {{ $person->fullName() }}@if($person->oib), OIB {{ $person->oib }}@endif
        @if($person->residence), prebivalište {{ $person->residence }}@endif
        (dalje: radnik).</p>

    <h2 class="h6 mt-4">Članak 1. Predmet</h2>
    <p>Radnik se zapošljava na poslovima radnog mjesta <strong>{{ $person->job_title ?: '—' }}</strong>{{ $person->location ? ' na lokaciji '.$person->location->name : '' }}.
        Vrsta ugovora: <strong>{{ $person->contract_type?->label() ?: 'nije unesena' }}</strong>.
        Odjel: <strong>{{ $person->department?->name ?: 'nije dodijeljen' }}</strong>.</p>

    <h2 class="h6">Članak 2. Trajanje i početak rada</h2>
    <p>{{ $durationText }} {{ $trialText }}</p>

    <h2 class="h6">Članak 3. Radno vrijeme</h2>
    <p>{{ $hoursText }} Evidencija radnog vremena vodi se prema Pravilniku NN 55/2024.</p>

    <h2 class="h6">Članak 4. Godišnji odmor</h2>
    <p>Fond godišnjeg odmora iznosi <strong>{{ $person->annual_leave_days ?? 20 }}</strong> radnih dana u kalendarskoj godini, ako posebni uvjeti ili kolektivni ugovor ne određuju više.</p>

    <h2 class="h6">Članak 5. Plaća</h2>
    <p>Osnovna plaća i dodaci utvrđuju se internim aktom poslodavca odnosno aneksom ovog ugovora. Ovaj ispis ne sadrži iznose plaće.</p>

    <h2 class="h6">Članak 6. Završne odredbe</h2>
    <p>Na odnose koji nisu uređeni ovim ugovorom primjenjuju se Zakon o radu i internim akti poslodavca. Ugovor je sastavljen u dva istovjetna primjerka, po jedan za svaku stranku.</p>

    <p class="small text-muted">Ispis je sastavljen iz kartice radnika ({{ $issuer->name }}, {{ $issuedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}). Nije zamjena za ovjereni ugovor ako stranke nisu potpisale papirnati ili kvalificirani elektronički primjerak.</p>

    <div class="row mt-5">
        <div class="col-6">
            <div class="small text-muted">Radnik</div>
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
