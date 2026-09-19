@php
    use App\Support\OrganizationThemes;

    $palette = OrganizationThemes::paletteFor($organization ?? null);
@endphp
<style>
    :root {
        --primarna-zelena: {{ $palette['primary'] }};
        --primarna-tamna: {{ $palette['dark'] }};
        --svijetlo-zelena: {{ $palette['light'] }};
        --bordo-crvena: {{ $palette['accent'] }};
        --zlatna-tradicija: {{ $palette['gold'] }};
        --tekst-tamni: {{ $palette['text'] }};
        --tema: var(--primarna-zelena);
        --tema-svijetla: var(--svijetlo-zelena);
        --tema-sjena-fokus: {{ OrganizationThemes::cssRgba($palette['primary'], 0.15) }};
        --tema-rub-tablica: {{ OrganizationThemes::cssRgba($palette['primary'], 0.18) }};
        --tema-greska-svijetla: #fff5f5;
    }
</style>
