@extends('layouts.guest')

@section('title', 'Registracija tvrtke — SuperSkyCrew')
@section('guest-width', 'col-lg-8')

@section('content')
<div class="kartica-kontejner">
    <img src="{{ asset('brand/superskycrew-lockup.png') }}" alt="SuperSkyCrew" class="app-guest-lockup">
    <h1 class="h4 text-tema mb-2">Registracija tvrtke</h1>
    <p class="text-muted small mb-4">Otvorite SuperSkyCrew račun za tvrtku, obrt ili udrugu. Nakon slanja super-administrator odobrava pristup.</p>

    @if(!empty($isLoggedIn))
        <div class="alert alert-success small">
            Prijavljeni ste kao <strong>{{ auth()->user()->email }}</strong>.
            Nova tvrtka bit će povezana s ovim računom (uloga vlasnika).
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger small">
            <strong>Registracija nije spremljena.</strong> Ispravite označena polja:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.organization') }}" id="formRegistracija">
        @csrf

        <h2 class="h6 text-tema">Podaci tvrtke</h2>
        <div class="mb-3">
            <label class="form-label" for="name">Naziv tvrtke</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" autocomplete="organization">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="organization_type">Tip</label>
            <select class="form-select @error('organization_type') is-invalid @enderror" name="organization_type" id="organization_type">
                @foreach(\App\Enums\OrganizationType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('organization_type', 'company') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('organization_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="oib">OIB</label>
                <input type="text" name="oib" id="oib" class="form-control @error('oib') is-invalid @enderror" value="{{ old('oib') }}" required maxlength="11" inputmode="numeric" pattern="\d{11}" autocomplete="off">
                @error('oib')<div class="invalid-feedback">{{ $message }}</div>@else
                    <div class="form-text">11 znamenki, bez razmaka.</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="organization_email">E-mail tvrtke</label>
                <input type="email" name="organization_email" id="organization_email" class="form-control @error('organization_email') is-invalid @enderror" value="{{ old('organization_email') }}" required maxlength="255" autocomplete="email">
                @error('organization_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="phone">Telefon</label>
                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="50" autocomplete="tel">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="city">Grad</label>
                <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" maxlength="100" autocomplete="address-level2">
                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <h2 class="h6 text-tema mt-2">Paket</h2>
        <p class="text-muted small">Limit je broj aktivnih osoba u evidenciji. Super-administrator može kasnije promijeniti paket.</p>
        <div class="row g-3 mb-4" role="radiogroup" aria-label="Odabir paketa">
            @foreach($plans as $plan)
                @php $checked = old('plan', $selectedPlan) === $plan['slug']; @endphp
                <div class="col-md-4">
                    <label class="plan-kartica {{ $checked ? 'aktivna' : '' }}">
                        <input type="radio" name="plan" value="{{ $plan['slug'] }}" class="form-check-input plan-kartica-radio" @checked($checked) required>
                        <div class="fw-semibold text-tema">{{ $plan['name'] }}</div>
                        <div class="small text-muted">{{ $plan['summary'] }}</div>
                        @if($plan['recommended'])
                            <span class="badge text-bg-light border mt-2">Preporučeno</span>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>
        @error('plan')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

        @unless($isLoggedIn)
            <hr>
            <h2 class="h6 text-tema">Vlasnički račun</h2>
            <div class="mb-3">
                <label class="form-label" for="admin_name">Ime i prezime</label>
                <input type="text" name="admin_name" id="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required maxlength="255" autocomplete="name">
                @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="admin_email">E-mail</label>
                <input type="email" name="admin_email" id="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required maxlength="255" autocomplete="username">
                @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Lozinka</label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@else
                    <div class="form-text">Najmanje 8 znakova.</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Potvrda lozinke</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
        @endunless

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Natrag na prijavu</a>
            <button type="submit" class="btn btn-primary">Registriraj tvrtku</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('input[name="plan"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.querySelectorAll('.plan-kartica').forEach(function (card) {
            const input = card.querySelector('input[name="plan"]');
            card.classList.toggle('aktivna', Boolean(input && input.checked));
        });
    });
});
</script>
@endpush
