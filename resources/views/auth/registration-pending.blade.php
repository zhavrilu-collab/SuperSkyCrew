@extends('layouts.guest')

@section('title', 'Čekanje odobrenja — SuperSkyCrew')
@section('guest-width', 'col-lg-6')

@section('content')
<div class="kartica-kontejner">
    <img src="{{ asset('brand/superskycrew-lockup.png') }}" alt="SuperSkyCrew" class="app-guest-lockup">
    <h1 class="h4 text-tema mb-3">Registracija na čekanju</h1>
    <p class="text-muted">Super-administrator mora odobriti tvrtku prije pristupa SuperSkyCrewu.</p>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <ul class="list-group mb-3">
        @foreach($pendingOrganizations as $organization)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $organization->name }}</span>
                <span class="badge bg-warning text-dark">{{ $organization->status->label() }}</span>
            </li>
        @endforeach
    </ul>

    <p class="small text-muted mb-0">Stranica se automatski osvježava svakih 15 sekundi.</p>
</div>
<script>
setInterval(() => {
    fetch('{{ route('registration.pending.status') }}')
        .then(r => r.json())
        .then(data => { if (!data.pending && data.redirect_url) window.location = data.redirect_url; });
}, 15000);
</script>
@endsection
