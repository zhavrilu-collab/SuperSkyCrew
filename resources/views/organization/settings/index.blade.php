@extends('layouts.organization')

@php
    $isUstroj = $tab === 'organizacija' && $section === 'ustroj';
    $isOsnovni = $tab === 'organizacija' && $section === 'osnovni-podaci';
    $hideSettingsChrome = $isUstroj || $isOsnovni;
    $settingsHeading = $isUstroj
        ? (\App\Support\StructureCatalog::tabs()[\App\Support\StructureCatalog::resolve($katalog ?? null)] ?? 'Ustroj tvrtke')
        : ($isOsnovni ? 'Osnovni podaci' : 'Postavke - '.\App\Support\SettingsCatalog::tabLabel($tab));
@endphp

@section('title', $settingsHeading)
@section('nav-suffix', $settingsHeading)

@section('content')
@if($isOsnovni)
<div class="page-heading">
    <h1>Osnovni podaci</h1>
    <p class="text-muted mb-0">Službeni naziv, OIB, adresa, e-mail i tip organizacije.</p>
</div>
@elseif(! $isUstroj)
<div class="page-heading">
    <h1>{{ $settingsHeading }}</h1>
    <p class="text-muted mb-0">{{ \App\Support\SettingsCatalog::tabDescription($tab) }}</p>
</div>
@endif

<div class="kartica-kontejner postavke-tab @if($isUstroj) kartica-kontejner--platno @endif">
    @unless($hideSettingsChrome)
    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
        @foreach($catalogTabs as $catalogItem)
            <li class="nav-item">
                <a class="nav-link @if($tab === $catalogItem['key']) active @endif"
                   href="{{ route('organization.settings.index', array_filter(['slug' => $organization->slug, 'tab' => $catalogItem['key'], 'section' => $catalogItem['section']])) }}">
                    {{ $catalogItem['label'] }}
                </a>
            </li>
        @endforeach
    </ul>

    @if($sections !== [])
        <ul class="nav nav-pills settings-subnav mb-4 flex-wrap gap-1">
            @foreach($sections as $key => $label)
                <li class="nav-item">
                    <a class="nav-link @if($section === $key) active @endif"
                       href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => $tab, 'section' => $key]) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
    @endunless

    @include('organization.settings.section')
</div>
@endsection
