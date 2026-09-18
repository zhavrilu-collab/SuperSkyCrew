<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#212529">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Prijava / odjava — {{ $organization->name }}</title>
    <link rel="manifest" href="{{ asset('clock-manifest.json') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .clock-btn { min-height: 88px; font-size: 1.5rem; }
        .state-pill { font-size: 1.1rem; }
    </style>
</head>
<body class="bg-dark text-white">
<div class="container py-4" style="max-width: 480px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('organization.dashboard', $organization->slug) }}" class="text-white-50 text-decoration-none">Natrag</a>
        <span class="small text-white-50">{{ now()->timezone(config('app.timezone'))->format('H:i') }}</span>
    </div>

    <p class="text-white-50 mb-1">{{ $organization->name }}</p>
    <h1 class="h3 mb-4">{{ $person->fullName() }}</h1>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="text-center mb-4">
        @if($clockedIn && $currentState === \App\Enums\PunchType::BreakStart)
            <span class="badge rounded-pill text-bg-warning state-pill">Na pauzi</span>
        @elseif($clockedIn)
            <span class="badge rounded-pill text-bg-success state-pill">Prijavljeni</span>
        @else
            <span class="badge rounded-pill text-bg-secondary state-pill">Niste prijavljeni</span>
        @endif
    </div>

    <form method="POST" action="{{ route('organization.clock.store', $organization->slug) }}" id="clock-form" class="d-grid gap-3">
        @csrf
        <input type="hidden" name="type" id="punch-type" value="{{ $nextType->value }}">
        <input type="hidden" name="from_pwa" value="1">
        <input type="hidden" name="client_event_id" id="client-event-id">
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <input type="hidden" name="gps_accuracy" id="gps-accuracy">
        <button class="btn {{ $clockedIn ? 'btn-light' : 'btn-success' }} clock-btn" type="submit">
            {{ $nextType->label() }}
        </button>
        @if($clockedIn && $currentState !== \App\Enums\PunchType::BreakStart)
            <button class="btn btn-outline-warning" type="submit" onclick="document.getElementById('punch-type').value='break_start'">Početak pauze</button>
        @elseif($currentState === \App\Enums\PunchType::BreakStart)
            <button class="btn btn-outline-warning" type="submit" onclick="document.getElementById('punch-type').value='break_end'">Kraj pauze</button>
        @endif
    </form>

    <h2 class="h6 mt-5 mb-3 text-white-50">Danas</h2>
    <ul class="list-unstyled">
        @forelse($todayPunches as $punch)
            <li class="d-flex justify-content-between border-bottom border-secondary py-2 {{ $punch->corrections->isNotEmpty() ? 'text-white-50 text-decoration-line-through' : '' }}">
                <span>
                    {{ $punch->type->label() }}
                    @if($punch->isCorrection()) <span class="small">ispravak</span> @endif
                </span>
                <span>
                    {{ $punch->occurred_at_device->timezone(config('app.timezone'))->format('H:i') }} · {{ $punch->channel->label() }}
                    @if($punch->corrections->isEmpty())
                        <a class="small text-info ms-2" href="{{ route('organization.requests.create', [$organization->slug, 'punch_id' => $punch->id]) }}">Ispravak</a>
                    @endif
                </span>
            </li>
        @empty
            <li class="text-white-50">Još nema prijava.</li>
        @endforelse
    </ul>
</div>
<script>
    document.getElementById('client-event-id').value = crypto.randomUUID();
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('latitude').value = pos.coords.latitude;
            document.getElementById('longitude').value = pos.coords.longitude;
            document.getElementById('gps-accuracy').value = Math.round(pos.coords.accuracy);
        }, function () {}, { enableHighAccuracy: true, timeout: 4000, maximumAge: 30000 });
    }
</script>
</body>
</html>
