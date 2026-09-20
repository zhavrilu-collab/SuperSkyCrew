<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $organization->themePalette()['primary'] }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Prijava / odjava — {{ $organization->name }}</title>
    <link rel="manifest" href="{{ route('organization.clock.manifest', $organization->slug) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.platform-styles')
    @include('partials.organization-theme', ['organization' => $organization])
    <style>
        body { background: var(--primarna-tamna) !important; color: #fff; }
        .clock-btn { min-height: 88px; font-size: 1.5rem; }
        .state-pill { font-size: 1.1rem; }
        a { color: var(--zlatna-tradicija); }
        .clock-meta { background: rgba(255,255,255,.06); border-radius: 12px; padding: 12px 14px; }
    </style>
</head>
<body>
<div class="container py-4" style="max-width: 480px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('organization.dashboard', $organization->slug) }}" class="text-white-50 text-decoration-none">Natrag</a>
        <span class="small text-white-50">{{ now()->timezone(config('app.timezone'))->format('H:i') }}</span>
    </div>

    <p class="text-white-50 mb-1">{{ $organization->name }}</p>
    <h1 class="h3 mb-3">{{ $person->fullName() }}</h1>

    <div class="clock-meta mb-4 d-flex justify-content-between gap-3">
        <div>
            <div class="small text-white-50">Tjedni fond</div>
            <div class="fw-semibold">{{ number_format($weekMinutes / 60, 1) }} h</div>
        </div>
        <div class="text-end">
            <a class="small" href="{{ route('organization.timesheet.mine', $organization->slug) }}">Moj tjedan</a>
            <span class="text-white-50"> · </span>
            <a class="small" href="{{ route('organization.requests.create', $organization->slug) }}">Ispravak</a>
        </div>
    </div>
    @if($pushEnabled ?? false)
        <button class="btn btn-outline-light btn-sm mb-3" type="button" id="clock-push-enable">Uključi obavijesti</button>
    @endif
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div id="clock-flash" class="alert d-none" role="status"></div>

    <div class="text-center mb-4">
        @if($clockedIn && $currentState === \App\Enums\PunchType::BreakStart)
            <span class="badge rounded-pill text-bg-warning state-pill" id="clock-state">Na pauzi</span>
        @elseif($clockedIn)
            <span class="badge rounded-pill text-bg-success state-pill" id="clock-state">Prijavljeni</span>
        @else
            <span class="badge rounded-pill text-bg-secondary state-pill" id="clock-state">Niste prijavljeni</span>
        @endif
    </div>

    <form method="POST" action="{{ route('organization.clock.store', $organization->slug) }}" id="clock-form" class="d-grid gap-3" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" id="punch-type" value="{{ $nextType->value }}">
        <input type="hidden" name="from_pwa" value="1">
        <input type="hidden" name="client_event_id" id="client-event-id">
        <input type="hidden" name="device_id" id="device-id">
        <input type="hidden" name="offline" id="offline" value="0">
        <input type="hidden" name="occurred_at" id="occurred-at">
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">
        <input type="hidden" name="gps_accuracy" id="gps-accuracy">
        @if($requirePhoto ?? false)
            <label class="form-label text-white-50" for="photo">Fotografija prijave</label>
            <input class="form-control" type="file" name="photo" id="photo" accept="image/*" capture="user" required>
            <p class="small text-white-50 mb-0">Kratko se čuva, bez prepoznavanja lica.</p>
        @endif
        <button class="btn {{ $clockedIn ? 'btn-light' : 'btn-primary' }} clock-btn" type="submit" id="clock-submit">
            {{ $nextType->label() }}
        </button>
        @if($clockedIn && $currentState !== \App\Enums\PunchType::BreakStart)
            <button class="btn btn-outline-warning" type="submit" data-punch-type="break_start">Početak pauze</button>
        @elseif($currentState === \App\Enums\PunchType::BreakStart)
            <button class="btn btn-outline-warning" type="submit" data-punch-type="break_end">Kraj pauze</button>
        @endif
    </form>

    <div id="clock-queue" class="mt-4 d-none">
        <h2 class="h6 text-white-50 mb-2">Čeka slanje</h2>
        <ul class="list-unstyled mb-0" id="clock-queue-list"></ul>
        <p class="small text-white-50 mb-0 mt-2">Spremljeno na uređaju. Šalje se čim bude mreže. Vrijeme prijave ostaje iz trenutka događaja.</p>
    </div>

    <h2 class="h6 mt-5 mb-3 text-white-50">Danas</h2>
    <ul class="list-unstyled">
        @forelse($todayPunches as $punch)
            <li class="d-flex justify-content-between border-bottom border-secondary py-2 {{ $punch->corrections->isNotEmpty() ? 'text-white-50 text-decoration-line-through' : '' }}">
                <span>
                    {{ $punch->type->label() }}
                    @if($punch->isCorrection()) <span class="small">ispravak</span> @endif
                    @if($punch->offline) <span class="small text-warning">offline</span> @endif
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

    @if($openExceptions->isNotEmpty())
        <h2 class="h6 mt-4 mb-3 text-white-50">Otvorene iznimke</h2>
        <ul class="list-unstyled">
            @foreach($openExceptions as $entry)
                <li class="d-flex justify-content-between border-bottom border-secondary py-2">
                    <span>{{ $entry->work_date->format('d.m.') }} · {{ $entry->exceptionLabel() }}</span>
                    <a class="small" href="{{ route('organization.timesheet.day', [$organization->slug, $person, $entry->work_date->toDateString()]) }}">Dan</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
<script>
    window.HR_CLOCK = {
        allowOffline: {{ ($allowOffline ?? true) ? 'true' : 'false' }},
        requirePhoto: {{ ($requirePhoto ?? false) ? 'true' : 'false' }},
        pushEnabled: {{ ($pushEnabled ?? false) ? 'true' : 'false' }}
    };
</script>
<script src="{{ asset('js/clock-pwa.js') }}"></script>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/clock-sw.js').catch(function () {});
    }
</script>
</body>
</html>
