<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111111">
    <title>Kiosk — {{ $organization->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; }
        .pin-btn { min-height: 72px; font-size: 1.75rem; }
        .clock-btn { min-height: 96px; font-size: 1.6rem; }
        .pin-dots { letter-spacing: 0.4rem; font-size: 2rem; }
    </style>
</head>
<body class="bg-dark text-white">
<div class="container py-4" style="max-width: 420px;">
    <p class="text-white-50 mb-1">{{ $organization->name }}</p>
    <p class="small text-white-50 mb-4">{{ $location->name }}</p>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if($person)
        <h1 class="h3 mb-3">{{ $person->fullName() }}</h1>
        <div class="text-center mb-4">
            @if($clockedIn && $currentState === \App\Enums\PunchType::BreakStart)
                <span class="badge rounded-pill text-bg-warning fs-6">Na pauzi</span>
            @elseif($clockedIn)
                <span class="badge rounded-pill text-bg-success fs-6">Prijavljeni</span>
            @else
                <span class="badge rounded-pill text-bg-secondary fs-6">Niste prijavljeni</span>
            @endif
        </div>
        <form method="POST" action="{{ route('organization.kiosk.punch', [$organization->slug, $location->kiosk_token]) }}" class="d-grid gap-3">
            @csrf
            <input type="hidden" name="type" id="punch-type" value="{{ $nextType->value }}">
            <input type="hidden" name="client_event_id" id="client-event-id">
            <button class="btn {{ $clockedIn ? 'btn-light' : 'btn-success' }} clock-btn" type="submit">{{ $nextType->label() }}</button>
            @if($clockedIn && $currentState !== \App\Enums\PunchType::BreakStart)
                <button class="btn btn-outline-warning" type="submit" onclick="document.getElementById('punch-type').value='break_start'">Početak pauze</button>
            @elseif($currentState === \App\Enums\PunchType::BreakStart)
                <button class="btn btn-outline-warning" type="submit" onclick="document.getElementById('punch-type').value='break_end'">Kraj pauze</button>
            @endif
        </form>
        <form method="POST" action="{{ route('organization.kiosk.reset', [$organization->slug, $location->kiosk_token]) }}" class="mt-4">
            @csrf
            <button class="btn btn-link text-white-50" type="submit">Nisam ja</button>
        </form>
        <script>
            document.getElementById('client-event-id').value = crypto.randomUUID();
            setTimeout(function () { document.querySelector('form[action*="odjava-ekrana"]').submit(); }, 45000);
        </script>
    @else
        <h1 class="h3 mb-4">Unesite PIN</h1>
        <form method="POST" action="{{ route('organization.kiosk.identify', [$organization->slug, $location->kiosk_token]) }}" id="pin-form">
            @csrf
            <input type="password" inputmode="numeric" name="pin" id="pin" class="form-control form-control-lg text-center pin-dots mb-4" maxlength="6" autocomplete="off" required>
            <div class="row g-2 mb-3">
                @foreach([1,2,3,4,5,6,7,8,9] as $digit)
                    <div class="col-4"><button class="btn btn-outline-light w-100 pin-btn" type="button" data-digit="{{ $digit }}">{{ $digit }}</button></div>
                @endforeach
                <div class="col-4"><button class="btn btn-outline-secondary w-100 pin-btn" type="button" id="pin-del">⌫</button></div>
                <div class="col-4"><button class="btn btn-outline-light w-100 pin-btn" type="button" data-digit="0">0</button></div>
                <div class="col-4"><button class="btn btn-success w-100 pin-btn" type="submit">OK</button></div>
            </div>
        </form>
        <script>
            const pin = document.getElementById('pin');
            document.querySelectorAll('[data-digit]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (pin.value.length < 6) pin.value += btn.getAttribute('data-digit');
                });
            });
            document.getElementById('pin-del').addEventListener('click', function () {
                pin.value = pin.value.slice(0, -1);
            });
        </script>
    @endif
</div>
</body>
</html>
