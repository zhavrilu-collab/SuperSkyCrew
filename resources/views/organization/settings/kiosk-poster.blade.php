@extends('layouts.print')

@section('title', 'QR kioska — '.$location->name)

@section('toolbar')
    <a href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'vrijeme', 'section' => 'lokacije']) }}" class="small">← Lokacije</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <div class="text-center">
        <p class="text-muted small mb-1">Kiosk tableta</p>
        <h1 class="h3 mb-1">{{ $location->name }}</h1>
        <p class="mb-4">Skenirajte da otvorite kiosk ove lokacije. Osoba se zatim identificira PIN-om ili vlastitim QR-om.</p>
        <div class="qr-poster mx-auto mb-3" data-qr="{{ $kioskUrl }}"></div>
        <p class="small text-break">{{ $kioskUrl }}</p>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="{{ asset('js/qr-draw.js') }}"></script>
@endpush
