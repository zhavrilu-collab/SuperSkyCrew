@extends('layouts.print')

@section('title', 'Ulazni QR — '.$location->name)

@section('toolbar')
    <a href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'vrijeme', 'section' => 'lokacije']) }}" class="small">← Lokacije</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <div class="text-center">
        <p class="text-muted small mb-1">Ulazni QR / NFC</p>
        <h1 class="h3 mb-1">{{ $location->name }}</h1>
        <p class="mb-4">Zaposlenik skenira ovaj kod (prijavljen u aplikaciji) da se prijavi ili odjavi na ulazu.</p>
        <div class="qr-poster mx-auto mb-3" data-qr="{{ $entranceUrl }}"></div>
        <p class="small text-break">{{ $entranceUrl }}</p>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="{{ asset('js/qr-draw.js') }}"></script>
@endpush
