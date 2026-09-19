@extends('layouts.print')

@section('title', 'Iskaznica kioska — '.$person->fullName())

@section('toolbar')
    <a href="{{ route('organization.people.edit', [$organization->slug, $person, 'tab' => 'zaposlenje']) }}" class="small">← Kartica</a>
    <div class="d-flex gap-2">
        @if($canRotate)
            <form method="POST" action="{{ route('organization.people.qr.rotate', [$organization->slug, $person]) }}" onsubmit="return confirm('Stari QR prestaje vrijediti. Nastaviti?');">
                @csrf
                <button class="btn btn-outline-secondary" type="submit">Obnovi QR</button>
            </form>
        @endif
        <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
    </div>
@endsection

@section('content')
    @if(session('status'))
        <div class="alert alert-success no-print">{{ session('status') }}</div>
    @endif
    <div class="text-center">
        <p class="text-muted small mb-1">Iskaznica za kiosk</p>
        <h1 class="h3 mb-1">{{ $person->fullName() }}</h1>
        <p class="mb-4">{{ $person->jobLabel() }}@if($person->location) · {{ $person->location->name }}@endif</p>
        <div class="qr-iskaznica mx-auto mb-3" data-qr="{{ $payload }}"></div>
        <p class="small text-muted mb-0">Skenirajte na kiosku lokacije. PIN ne ispisujte na iskaznici.</p>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script src="{{ asset('js/qr-draw.js') }}"></script>
@endpush
