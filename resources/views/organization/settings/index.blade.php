@extends('layouts.organization')

@php
    $settingsHeading = 'Postavke - '.\App\Support\SettingsCatalog::tabLabel($tab);
@endphp

@section('title', $settingsHeading)
@section('nav-suffix', $settingsHeading)

@section('content')
<div class="page-heading">
    <h1>{{ $settingsHeading }}</h1>
    <p class="text-muted mb-0">{{ \App\Support\SettingsCatalog::tabDescription($tab) }}</p>
</div>

<div class="kartica-kontejner postavke-tab">
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

    @include('organization.settings.section')
</div>
@endsection
