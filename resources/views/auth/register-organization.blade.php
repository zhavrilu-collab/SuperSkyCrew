@extends('layouts.guest')

@section('title', 'Registracija tvrtke — SuperSkyCrew')
@section('guest-width', 'col-lg-8')

@section('content')
<div class="kartica-kontejner">
    <img src="{{ asset('brand/superskycrew-lockup.png') }}" alt="SuperSkyCrew" class="app-guest-lockup">
    <h1 class="h4 text-tema mb-2">Registracija tvrtke</h1>
    <p class="text-muted small mb-4">Otvorite SuperSkyCrew račun za tvrtku, obrt ili udrugu. Nakon odobrenja slijedi {{ $trialDays }} dana paketa {{ $trialPlanLabel }}.</p>

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
        @php
            $selectedType = \App\Enums\OrganizationType::tryFrom((string) old('organization_type', 'company'))
                ?? \App\Enums\OrganizationType::Company;
        @endphp

        <div class="mb-3">
            <label class="form-label" for="organization_type">Tip</label>
            <select class="form-select @error('organization_type') is-invalid @enderror" name="organization_type" id="organization_type">
                @foreach(\App\Enums\OrganizationType::cases() as $type)
                    <option value="{{ $type->value }}" @selected($selectedType === $type)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('organization_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3 {{ $selectedType->usesCourtRegister() ? '' : 'd-none' }}" id="sudregLookupBox">
            <label class="form-label" for="courtLookupQ">Sudski registar</label>
            <input type="search" id="courtLookupQ" class="form-control" placeholder="OIB, MBS ili naziv tvrtke…" autocomplete="off">
            <div class="form-text" id="courtLookupHint">
                @if($courtRegisterConfigured)
                    Podaci se dohvaćaju uživo iz Sudskog registra (nije lokalna kopija).
                @else
                    Sudski registar nije spojen (nedostaje SUDREG_CLIENT_ID). Unesite podatke ručno ili registrirajte besplatni API na sudreg-data.gov.hr.
                @endif
            </div>
            <div id="courtLookupStatus" class="small text-muted mt-2"></div>
            <div id="courtLookupResults" class="list-group mt-2 d-none" style="max-height: 18rem; overflow-y: auto;"></div>
        </div>

        <div class="mb-3 {{ $selectedType->usesCraftsRegister() ? '' : 'd-none' }}" id="craftLookupBox">
            <label class="form-label">Obrtni registar</label>
            <p class="form-text mb-2">
                Ministarstvo ne nudi javni API za obrte (za razliku od Sudskog registra).
                Obrt nema vlastiti OIB ni MBS — unesite OIB obrtnika i podatke s izvatka.
            </p>
            <a class="btn btn-outline-secondary btn-sm" href="{{ $craftsRegisterSearchUrl ?? \App\Enums\OrganizationType::CRAFTS_REGISTER_SEARCH_URL }}" target="_blank" rel="noopener">Otvori službeni pretraživač obrta</a>
        </div>

        <h2 class="h6 text-tema" id="orgFieldsHeading">Podaci organizacije</h2>
        <div class="mb-3">
            <label class="form-label" for="name" id="nameLabel">{{ $selectedType->nameLabel() }}</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" autocomplete="organization">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="oib" id="oibLabel">OIB</label>
                <input type="text" name="oib" id="oib" class="form-control @error('oib') is-invalid @enderror" value="{{ old('oib') }}" required maxlength="11" inputmode="numeric" pattern="\d{11}" autocomplete="off">
                @error('oib')<div class="invalid-feedback">{{ $message }}</div>@else
                    <div class="form-text" id="oibHint">{{ $selectedType->oibHint() }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="organization_email">E-mail</label>
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
        <div class="mb-3">
            <label class="form-label" for="street">Adresa sjedišta</label>
            <input type="text" name="street" id="street" class="form-control @error('street') is-invalid @enderror" value="{{ old('street') }}" maxlength="255" autocomplete="street-address">
            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row" id="registryNumberRow">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="mbs" id="mbsLabel">{{ $selectedType->registryNumberLabel() }}</label>
                <input type="text" name="mbs" id="mbs" class="form-control @error('mbs') is-invalid @enderror" value="{{ old('mbs') }}" maxlength="32" autocomplete="off">
                @error('mbs')<div class="invalid-feedback">{{ $message }}</div>@else
                    <div class="form-text" id="mbsHint">{{ $selectedType->registryNumberHint() }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="nkd">NKD</label>
                <input type="text" name="nkd" id="nkd" class="form-control @error('nkd') is-invalid @enderror" value="{{ old('nkd') }}" maxlength="16" autocomplete="off">
                @error('nkd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <h2 class="h6 text-tema mt-2">Paket</h2>
        <p class="text-muted small">
            Nakon odobrenja: <strong>{{ $trialDays }} dana</strong> probnog perioda paketa
            <strong>{{ $trialPlanLabel }}</strong>
            (ili višeg ako odaberete Premium). Super-administrator može kasnije produljiti trial.
        </p>
        <div class="row g-3 mb-4" role="radiogroup" aria-label="Odabir paketa">
            @foreach($plans as $plan)
                @php $checked = old('plan', $selectedPlan) === $plan['slug']; @endphp
                <div class="col-md-4">
                    <label class="plan-kartica {{ $checked ? 'aktivna' : '' }}">
                        <input type="radio" name="plan" value="{{ $plan['slug'] }}" class="form-check-input plan-kartica-radio" @checked($checked) required>
                        <div class="fw-semibold text-tema">{{ $plan['name'] }}</div>
                        <div class="small text-muted">{{ $plan['summary'] }}</div>
                        @if($plan['recommended'])
                            <span class="badge text-bg-light border mt-2">Preporučeno / trial</span>
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

(function () {
    const lookupUrl = @json($courtRegisterLookupUrl ?? '');
    const configured = @json((bool) ($courtRegisterConfigured ?? false));
    const typeSelect = document.getElementById('organization_type');
    const box = document.getElementById('sudregLookupBox');
    const craftBox = document.getElementById('craftLookupBox');
    const qInput = document.getElementById('courtLookupQ');
    const statusEl = document.getElementById('courtLookupStatus');
    const resultsEl = document.getElementById('courtLookupResults');
    const typeCopy = {
        company: {
            name: 'Naziv tvrtke',
            oib: '11 znamenki, bez razmaka.',
            mbs: 'MBS',
            mbsHint: 'Matični broj subjekta iz Sudskog registra.',
        },
        craft: {
            name: 'Naziv obrta',
            oib: 'OIB obrtnika (fizičke osobe). Obrt nema vlastiti OIB.',
            mbs: 'Registarski broj obrta',
            mbsHint: 'Broj iz izvatka Obrtnog registra. Nije MBS — DZS ga obrtima ne dodjeljuje.',
        },
        nonprofit: {
            name: 'Naziv udruge',
            oib: '11 znamenki, bez razmaka.',
            mbs: 'MBS',
            mbsHint: 'Po potrebi.',
        },
    };

    function currentType() {
        return (typeSelect && typeSelect.value) ? typeSelect.value : 'company';
    }

    function toggleBox() {
        const type = currentType();
        if (box) box.classList.toggle('d-none', type !== 'company');
        if (craftBox) craftBox.classList.toggle('d-none', type !== 'craft');
        const copy = typeCopy[type] || typeCopy.company;
        const nameLabel = document.getElementById('nameLabel');
        const oibHint = document.getElementById('oibHint');
        const mbsLabel = document.getElementById('mbsLabel');
        const mbsHint = document.getElementById('mbsHint');
        if (nameLabel) nameLabel.textContent = copy.name;
        if (oibHint) oibHint.textContent = copy.oib;
        if (mbsLabel) mbsLabel.textContent = copy.mbs;
        if (mbsHint) mbsHint.textContent = copy.mbsHint;
    }
    if (typeSelect) typeSelect.addEventListener('change', toggleBox);
    toggleBox();

    if (!lookupUrl || !qInput || !resultsEl) return;

    function setStatus(text, isError) {
        if (!statusEl) return;
        statusEl.textContent = text || '';
        statusEl.className = 'small mt-2 ' + (isError ? 'text-danger' : 'text-muted');
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el && value) el.value = value;
    }

    function applyEntry(entry) {
        setValue('name', entry.name);
        setValue('oib', entry.oib);
        setValue('city', entry.city);
        setValue('organization_email', entry.email);
        setValue('mbs', entry.mbs);
        setValue('nkd', entry.nkd);
        setValue('street', entry.street);
        resultsEl.classList.add('d-none');
        resultsEl.innerHTML = '';
        qInput.value = entry.name || '';
        setStatus('Podaci su predpopunjeni iz Sudskog registra — provjerite ih prije slanja.', false);
    }

    function renderResults(results, query) {
        resultsEl.innerHTML = '';
        if (!results.length) {
            resultsEl.classList.add('d-none');
            setStatus('Nema pogodaka za „' + query + '".', true);
            return;
        }
        results.forEach(function (entry) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-2';
            btn.innerHTML = '<div class="fw-semibold">' + escapeHtml(entry.name || '—') + '</div>'
                + '<div class="small text-muted">OIB ' + escapeHtml(entry.oib || '—')
                + (entry.mbs ? ' · MBS ' + escapeHtml(entry.mbs) : '')
                + (entry.city ? '<br>' + escapeHtml(entry.street ? entry.street + ', ' + entry.city : entry.city) : '')
                + '</div>';
            btn.addEventListener('click', function () { applyEntry(entry); });
            resultsEl.appendChild(btn);
        });
        resultsEl.classList.remove('d-none');
        setStatus(results.length + ' prijedloga — kliknite za predpopunu.', false);
    }

    let debounceTimer = null;
    let activeController = null;
    let lastQuery = '';

    function runLookup(q) {
        q = (q || '').trim();
        if (q.length < 2) {
            resultsEl.classList.add('d-none');
            resultsEl.innerHTML = '';
            setStatus('', false);
            return;
        }
        if (q === lastQuery) return;
        lastQuery = q;
        if (!configured) {
            setStatus('Sudski registar nije spojen. Unesite podatke ručno.', true);
            return;
        }
        if (activeController) activeController.abort();
        activeController = new AbortController();
        setStatus('Pretraživanje Sudskog registra…', false);
        fetch(lookupUrl + '?q=' + encodeURIComponent(q), {
            headers: { Accept: 'application/json' },
            signal: activeController.signal,
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    if (!r.ok) throw new Error(data.message || 'Pretraga nije uspjela.');
                    return data;
                });
            })
            .then(function (data) {
                if ((qInput.value || '').trim() !== q) return;
                renderResults(data.results || [], q);
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                setStatus(err.message || 'Pretraga nije uspjela.', true);
            });
    }

    qInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = (qInput.value || '').trim();
        if (q.length < 2) {
            lastQuery = '';
            resultsEl.classList.add('d-none');
            resultsEl.innerHTML = '';
            setStatus('', false);
            return;
        }
        debounceTimer = setTimeout(function () { runLookup(q); }, 320);
    });
})();
</script>
@endpush
