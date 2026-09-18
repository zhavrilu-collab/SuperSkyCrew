<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rješenje o godišnjem odmoru {{ $requestItem->decisionNumber() }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            a { text-decoration: none; color: inherit; }
        }
        .decision { max-width: 720px; }
        .sign { min-height: 80px; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4 decision">
    <div class="d-flex justify-content-between mb-4 no-print">
        <a href="{{ route('organization.requests.show', [$organization->slug, $requestItem]) }}" class="small">← Zahtjev</a>
        <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
    </div>

    <p class="text-muted mb-1">{{ $organization->name }}@if($organization->oib) · OIB {{ $organization->oib }}@endif</p>
    <p class="text-end">Klasa: {{ $requestItem->decisionNumber() }}<br>
        Ur. broj: {{ $requestItem->id }}<br>
        {{ ($requestItem->decided_at ?? now())->timezone(config('app.timezone'))->format('d.m.Y.') }}</p>

    <h1 class="h4 text-center mt-4 mb-4">RJEŠENJE<br><span class="fs-6 fw-normal">o korištenju godišnjeg odmora</span></h1>

    <p>Poslodavac <strong>{{ $organization->name }}</strong> donosi rješenje o korištenju godišnjeg odmora za radnika:</p>

    <dl class="row">
        <dt class="col-sm-4">Ime i prezime</dt>
        <dd class="col-sm-8">{{ $person->fullName() }}</dd>
        <dt class="col-sm-4">OIB</dt>
        <dd class="col-sm-8">{{ $person->oib ?: '—' }}</dd>
        <dt class="col-sm-4">Radno mjesto</dt>
        <dd class="col-sm-8">{{ $person->job_title ?: '—' }}</dd>
    </dl>

    <p>Odobrava se korištenje godišnjeg odmora u trajanju od <strong>{{ $requestItem->days() }}</strong>
        radn{{ $requestItem->days() === 1 ? 'og dana' : 'ih dana' }}, u razdoblju
        od <strong>{{ \Carbon\Carbon::parse($requestItem->fromDate())->format('d.m.Y.') }}</strong>
        do <strong>{{ \Carbon\Carbon::parse($requestItem->toDate())->format('d.m.Y.') }}</strong>
        (radni dani: {{ implode(', ', array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d.m.Y.'), $requestItem->payload['dates'] ?? [])) }}).</p>

    <p>Nakon ovog korištenja, za {{ $leave['year'] }}. godinu preostaje
        <strong>{{ $leave['remaining'] }}</strong> dana godišnjeg odmora
        @if($leave['remaining_old'] !== null)
            (staro {{ $leave['remaining_old'] }} / novo {{ $leave['remaining_new'] }})
        @endif
        .</p>

    <p class="small text-muted">Rješenje se izdaje na temelju Zakona o radu i internog akta poslodavca. U šihtericu je upisana šifra GO.</p>

    <div class="row mt-5">
        <div class="col-6">
            <div class="small text-muted">Radnik (uvid)</div>
            <div class="sign border-bottom">&nbsp;</div>
            <div class="small">{{ $person->fullName() }}</div>
        </div>
        <div class="col-6 text-end">
            <div class="small text-muted">Za poslodavca</div>
            <div class="sign border-bottom">&nbsp;</div>
            <div class="small">{{ $requestItem->payload['decision']['approver_name'] ?? $requestItem->submittedBy?->name }}</div>
        </div>
    </div>
</div>
</body>
</html>
