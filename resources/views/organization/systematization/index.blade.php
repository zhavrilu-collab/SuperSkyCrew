@extends('layouts.organization')

@php
    $pogled = $pogled ?? 'opisi';
    $sistemaUrl = fn (?string $pog = null, $mjesto = false, ?string $na = null, ?string $q = null) => route('organization.systematization.index', array_filter([
        'slug' => $organization->slug,
        'pogled' => ($pog ?? $pogled) === 'opisi' ? null : ($pog ?? $pogled),
        'mjesto' => ($pog ?? $pogled) === 'akti'
            ? null
            : ($mjesto === false ? $selectedPosition?->id : $mjesto),
        'na' => ($pog ?? $pogled) === 'akti' ? null : ($na ?? $on->toDateString()),
        'q' => ($pog ?? $pogled) === 'akti' ? null : $q,
    ], fn ($value) => $value !== null && $value !== ''));
    $ustrojMjestoUrl = fn ($mjestoId = null) => route('organization.settings.index', array_filter([
        'slug' => $organization->slug,
        'tab' => 'organizacija',
        'section' => 'ustroj',
        'katalog' => 'mjesta',
        'mjesto' => $mjestoId,
        'na' => $on->toDateString(),
    ]));
@endphp

@section('title', $pogled === 'akti' ? 'Interni akti' : 'Opisi radnih mjesta')
@section('nav-suffix', 'Sistematizacija')

@section('content')
<div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>{{ $pogled === 'akti' ? 'Interni akti' : 'Opisi radnih mjesta' }}</h1>
        <p class="text-muted mb-0">
            @if($pogled === 'akti')
                Pravilnici i kodeksi organizacije. Nije isto što dosje radnika.
            @else
                Katalog uloga s dužnostima, kompetencijama i RAD1G šifrom na odabrani dan.
            @endif
        </p>
        <div class="ustroj-pilule" role="navigation" aria-label="Pogledi sistematizacije">
            <a class="ustroj-pilula @if($pogled === 'opisi') is-active @endif" href="{{ $sistemaUrl('opisi', false, $on->toDateString(), $q) }}">Opisi</a>
            <a class="ustroj-pilula @if($pogled === 'akti') is-active @endif" href="{{ $sistemaUrl('akti') }}">Akti</a>
        </div>
    </div>
    @if($pogled === 'opisi')
        <a href="{{ $ustrojMjestoUrl() }}" class="btn btn-primary">Novo radno mjesto</a>
    @endif
</div>

@if($pogled === 'akti')
    @include('organization.systematization.panel-acts')
@else
    @include('organization.systematization.panel-opisi')
@endif
@endsection
