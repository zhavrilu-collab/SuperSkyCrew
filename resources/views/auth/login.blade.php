@extends('layouts.guest')

@section('title', 'Prijava — SuperSkyCrew')

@section('content')
<div class="kartica-kontejner">
    <img src="{{ asset('brand/superskycrew-lockup.png') }}" alt="SuperSkyCrew" class="app-guest-lockup">
    <h1 class="h4 text-tema mb-3">Prijava</h1>
    <p class="text-muted small">Prijava u SuperSkyCrew — platformu za upravljanje ljudskim resursima.</p>

    @if ($errors->any())
        <div class="alert alert-danger small">{{ $errors->first() }}</div>
    @endif

    @if($googleLoginUrl || $microsoftLoginUrl)
        <div class="d-grid gap-2 mb-3">
            @if($googleLoginUrl)
                <a href="{{ $googleLoginUrl }}" class="btn btn-outline-secondary btn-sm">Prijava s Googleom</a>
            @endif
            @if($microsoftLoginUrl)
                <a href="{{ $microsoftLoginUrl }}" class="btn btn-outline-secondary btn-sm">Prijava s Microsoftom</a>
            @endif
        </div>
        <div class="text-center text-muted small mb-3">ili</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Lozinka</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Zapamti me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Prijavi se</button>
    </form>

    @if(!empty($passwordResetUrl))
        <p class="text-center mt-3 mb-0 small">
            <a href="{{ $passwordResetUrl }}">Zaboravili ste lozinku?</a>
        </p>
    @endif

    <p class="text-center mt-2 mb-0 small">
        Nemate tvrtku? <a href="{{ route('register.organization') }}">Registrirajte novu</a>
    </p>
</div>
@endsection
