@extends('layouts.guest')

@section('title', 'Registracija organizacije — HR SaaS')
@section('guest-width', 'col-lg-7')

@section('content')
<div class="kartica-kontejner">
    <h1 class="h4 text-tema mb-3">Registracija organizacije</h1>
    <p class="text-muted small">Nakon registracije organizacija čeka odobrenje super-administratora. Možete prijaviti tvrtku, obrt ili udrugu.</p>

    @if ($errors->any())
        <div class="alert alert-danger small">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.organization') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">Naziv organizacije</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="organization_type">Tip</label>
            <select class="form-select" name="organization_type" id="organization_type">
                <option value="company" @selected(old('organization_type', 'company') === 'company')>Tvrtka / obrt</option>
                <option value="nonprofit" @selected(old('organization_type') === 'nonprofit')>Udruga / NPO</option>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="oib">OIB</label>
                <input type="text" name="oib" id="oib" class="form-control" value="{{ old('oib') }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="organization_email">E-mail organizacije</label>
                <input type="email" name="organization_email" id="organization_email" class="form-control" value="{{ old('organization_email') }}" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="phone">Telefon</label>
                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="city">Grad</label>
                <input type="text" name="city" id="city" class="form-control" value="{{ old('city') }}">
            </div>
        </div>

        @unless($isLoggedIn)
            <hr>
            <h2 class="h6">Vlasnički račun</h2>
            <div class="mb-3">
                <label class="form-label" for="admin_name">Ime i prezime</label>
                <input type="text" name="admin_name" id="admin_name" class="form-control" value="{{ old('admin_name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="admin_email">E-mail</label>
                <input type="email" name="admin_email" id="admin_email" class="form-control" value="{{ old('admin_email') }}">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Lozinka</label>
                <input type="password" name="password" id="password" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Potvrda lozinke</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
            </div>
        @endunless

        <button type="submit" class="btn btn-primary">Registriraj organizaciju</button>
    </form>
</div>
@endsection
