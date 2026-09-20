@php
    $activeTheme = \App\Support\OrganizationThemes::resolve($organization->theme_key);
    $themes = \App\Support\OrganizationThemes::builtinAll();
@endphp
<div id="izgled">
    <span class="fw-bold text-muted small d-block mb-2">TEMA IZGLEDA I PALETE BOJA</span>
    <form method="POST" action="{{ route('organization.settings.theme', $organization->slug) }}" id="formTemaOrganizacije" class="mb-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="theme_key" id="themeColorInput" value="{{ $activeTheme }}">
        <div class="mb-2">
            <label class="form-label small fw-bold mb-2">Zadana tema</label>
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
            <div class="form-text">Kliknite paletu za odabir. Pregled se odmah vidi na traci i izborniku; spremite za trajnu temu (shell, clock/kiosk).</div>
        </div>
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
window.THEME_PREVIEW = {
    savedTheme: @json($activeTheme),
    palettes: @json(\App\Support\OrganizationThemes::previewPayload($organization)),
};
</script>
<script src="{{ asset('js/theme-preview.js') }}"></script>
