@extends('layouts.guest')

@section('title', 'Cijene — SuperSkyCrew')
@section('guest-width', 'col-lg-10')

@section('content')
<div class="text-center mb-4">
    <img src="{{ asset('brand/superskycrew-lockup.png') }}" alt="SuperSkyCrew" class="app-guest-lockup">
    <h1 class="h3 text-tema">Paketi SuperSkyCrew</h1>
    <p class="text-muted mb-0">
        Isti HR sustav u svim paketima — birate veličinu kadra.
        @if($trialDays > 0)
            Probni period: <strong>{{ $trialDays }} dana</strong> paketa {{ $trialPlanLabel }} nakon odobrenja.
        @endif
    </p>
</div>

<div class="row g-3">
    @foreach($plans as $plan)
        <div class="col-md-4">
            <div class="kartica-kontejner h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold text-tema">{{ $plan['name'] }}</span>
                    @if($plan['recommended'])
                        <span class="badge text-bg-light border">trial</span>
                    @endif
                </div>
                <p class="small text-muted mb-3">{{ $plan['summary'] }}</p>
                <ul class="small mb-4 flex-grow-1">
                    @foreach($plan['highlights'] as $item)
                        <li class="mb-1">{{ $item }}</li>
                    @endforeach
                </ul>
                <a class="btn btn-primary w-100" href="{{ route('register.organization', ['plan' => $plan['slug']]) }}">Započni registraciju</a>
            </div>
        </div>
    @endforeach
</div>

<p class="text-center small text-muted mt-4 mb-0">
    <a class="text-tema" href="{{ route('home') }}">Natrag</a>
    · <a class="text-tema" href="{{ route('login') }}">Prijava</a>
</p>
@endsection
