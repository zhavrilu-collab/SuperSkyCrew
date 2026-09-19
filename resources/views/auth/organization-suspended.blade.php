@extends('layouts.guest')

@section('title', 'Pristup suspendiran — HR SaaS')
@section('guest-width', 'col-lg-6')

@section('content')
<div class="kartica-kontejner text-center">
    <h1 class="h4 text-tema mb-3">Pristup suspendiran</h1>
    <p class="text-muted mb-4">
        Pristup organizaciji <strong>{{ $organization->name }}</strong> je privremeno onemogućen.
        Kontaktirajte podršku ili super-administratora za više informacija.
    </p>
    <div class="d-flex justify-content-center gap-2">
        <a href="{{ route('organization.pick') }}" class="btn btn-outline-secondary">Odaberi drugu organizaciju</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Odjava</button>
        </form>
    </div>
</div>
@endsection
