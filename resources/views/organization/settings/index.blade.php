@extends('layouts.organization')

@php
    $isUstroj = $tab === 'organizacija' && $section === 'ustroj';
    $isOsnovni = $tab === 'organizacija' && $section === 'osnovni-podaci';
    $settingsHeading = $isUstroj
        ? (\App\Support\StructureCatalog::tabs()[\App\Support\StructureCatalog::resolve($katalog ?? null)] ?? 'Ustroj tvrtke')
        : \App\Support\SettingsCatalog::sectionLabel($tab, (string) $section);
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
    <p class="text-muted mb-0">{{ \App\Support\SettingsCatalog::sectionDescription($tab, (string) $section) }}</p>
</div>
@endif

<div class="kartica-kontejner postavke-tab @if($isUstroj) kartica-kontejner--platno @endif">
    @include('organization.settings.section')
</div>
@endsection
