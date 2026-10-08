@php
    $activeTheme = \App\Support\OrganizationThemes::resolve($organization->theme_key);
    $activeStyle = \App\Support\ThemeRecipes::effectiveStyle($organization->theme_style);
    $themes = \App\Support\OrganizationThemes::builtinAll();
    $themeStyles = \App\Support\ThemeRecipes::styles();
    $themeCards = \App\Support\OrganizationThemes::combinationPayload();
@endphp
<div id="izgled">
    <span class="fw-bold text-muted small d-block mb-2">BOJA TEME</span>
    <form method="POST" action="{{ route('organization.settings.theme', $organization->slug) }}" id="formTemaOrganizacije" class="mb-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="themeColorInput" value="{{ $activeTheme }}">
        <input type="hidden" name="theme_style" id="themeStyleInput" value="{{ $activeStyle }}">
        <img id="temaLogoPregled"
             src="{{ \App\Support\OrganizationThemes::productLogoUrl($activeTheme, true) }}"
             alt="SuperSkyCrew"
             class="tema-logo-pregled-img mb-2"
             data-product-logo="horizontal">
        <div class="mb-2">
            <label class="form-label small fw-bold mb-2">Službena boja</label>
            <div class="d-flex gap-2 mb-2 flex-wrap align-items-center" id="temaBojaIzbor">
                @foreach($themes as $key => $theme)
                    <button type="button"
                            class="tema-svatch @if($key === $activeTheme) aktivna @endif"
                            data-tema="{{ $key }}"
                            style="background:{{ $theme['primary'] }};"
                            title="{{ $theme['label'] }}"
                            aria-label="{{ $theme['label'] }}"></button>
                @endforeach
            </div>
            <div class="form-text">Boja bira logotip i obitelj nijansi. Klik odmah mijenja zaslon.</div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold mb-2">Tema</label>
            <div class="tema-smjerovi" id="temaSmjerIzbor">
                @foreach($themeStyles as $styleKey => $style)
                    @php($card = $themeCards[$activeTheme.'|'.$styleKey])
                    <button type="button"
                            class="tema-kartica @if($styleKey === $activeStyle) aktivna @endif"
                            data-stil="{{ $styleKey }}"
                            title="{{ $style['note'] }}"
                            aria-label="{{ $style['label'] }}">
                        <span class="tema-kartica-naziv">{{ $style['label'] }}</span>
                        <span class="tema-kartica-okvir">
                            <span class="tema-kartica-strana" style="background:{{ $card['sideBg'] ?? '#ffffff' }};color:{{ $card['idle'] ?? '#2a2a28' }}">
                                <span class="tema-kartica-kugla" style="background:{{ $card['logoMark'] }}"></span>
                                <span class="tema-kartica-red">Početna</span>
                                <span class="tema-kartica-red">Udruga</span>
                                <span class="tema-kartica-red">Članovi</span>
                                <span class="tema-kartica-red"><b>Komunikacija</b></span>
                                <span class="tema-kartica-stavka" style="background:{{ $card['navBg'] }};color:{{ $card['navFg'] }};border-left-color:{{ $card['navBar'] }};font-weight:{{ $card['navWeight'] ?? '650' }}">Poruke</span>
                                <span class="tema-kartica-red tema-kartica-pod">Predlošci</span>
                            </span>
                            <span class="tema-kartica-sadrzaj" style="background:{{ $card['light'] }};color:{{ $card['text'] }}">
                                <span class="tema-kartica-gumbi">
                                    <span class="tema-kartica-gumb" style="background:{{ $card['btnBg'] }};color:{{ $card['btnFg'] }};border-color:{{ $card['btnBorder'] }}">Poziv</span>
                                    <span class="tema-kartica-gumb tema-kartica-obrub" style="background:#fff;color:{{ $card['primary'] }};border-color:{{ $card['primary'] }}">Obavijest</span>
                                </span>
                                <span class="tema-kartica-lista">Ivan Pudak<br>Daria Macan</span>
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>
            <div class="form-text">Tema bira kako se nijanse slažu. Klik odmah pokazuje cijeli zaslon.</div>
        </div>
        @error('theme_key')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        @error('theme_style')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror
        <button type="submit" class="btn btn-success btn-sm btn-spremi">Spremi temu</button>
        <span id="temaPoruka" class="ms-2 small text-muted"></span>
    </form>

    <span class="fw-bold text-muted small d-block mb-2">LOGOTIP</span>
    <p class="form-text mb-2">Bijeli okvir u lijevom izborniku i na gornjoj traci.</p>
    @if($organization->logoUrl())
        <img src="{{ $organization->logoUrl() }}" alt="" class="navbar-brand-logo mb-2">
    @endif
    <form method="POST" action="{{ route('organization.settings.logo', $organization->slug) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-end">
        @csrf
        <div>
            <label class="form-label" for="logo">Datoteka</label>
            <input type="file" class="form-control form-control-sm" name="logo" id="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Spremi logotip</button>
    </form>
    @error('logo')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
    @if($organization->logo_path)
        <form method="POST" action="{{ route('organization.settings.logo', $organization->slug) }}" class="mt-2">
            @csrf
            <input type="hidden" name="remove" value="1">
            <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
        </form>
    @endif
</div>

<script>
window.THEME_PREVIEW = @json(\App\Support\OrganizationThemes::clientPreview($organization));
</script>
<script src="{{ asset('js/theme-preview.js') }}?v={{ filemtime(public_path('js/theme-preview.js')) }}"></script>
