<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evidencija radnog vremena — {{ $organization->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            a { text-decoration: none; color: inherit; }
        }
        .legend dt { font-family: monospace; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2 no-print">
        <a href="{{ route('organization.timesheet.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="small">← Šihterica</a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('organization.handovers.index', $organization->slug) }}">Predaja (čl. 5.)</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
        </div>
    </div>

    <h1 class="h4 mb-1">Evidencija o radnom vremenu</h1>
    <p class="text-muted">Pravilnik NN 55/2024, čl. 13.</p>

    <dl class="row">
        <dt class="col-sm-3">Poslodavac</dt>
        <dd class="col-sm-9">{{ $organization->name }}</dd>
        <dt class="col-sm-3">OIB</dt>
        <dd class="col-sm-9">{{ $organization->oib ?: '—' }}</dd>
        <dt class="col-sm-3">Razdoblje</dt>
        <dd class="col-sm-9">{{ $from->format('d.m.Y.') }} – {{ $to->format('d.m.Y.') }}</dd>
        <dt class="col-sm-3">Izradio</dt>
        <dd class="col-sm-9">{{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</dd>
    </dl>

    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered bg-white">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Datum</th>
                    <th>Početak</th>
                    <th>Završetak</th>
                    <th>Ukupno min</th>
                    <th>Pauza</th>
                    <th>Noć</th>
                    <th>Prekovr.</th>
                    <th>Nedj.</th>
                    <th>Blagdan</th>
                    <th>Odsutnost</th>
                    <th>Ods. min</th>
                    <th>Evid. min</th>
                    <th>Status</th>
                    <th>Iznimka</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell === null || $cell === '' ? '—' : $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="text-muted">Nema slogova u odabranom razdoblju.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h6">Značenje kratica (čl. 18. st. 2.)</h2>
    <dl class="row legend">
        @forelse($codes as $code)
            <dt class="col-sm-2">{{ $code->code }}</dt>
            <dd class="col-sm-10">{{ $code->name }}{{ $code->paid ? '' : ' · neplaćeno' }}</dd>
        @empty
            <dd class="col-12 text-muted mb-0">Šifrarnik odsutnosti nije popunjen.</dd>
        @endforelse
    </dl>

    <h2 class="h6 mt-4">Evidencija predaje (čl. 5. st. 5.–6.)</h2>
    @forelse($handovers as $handover)
        <p class="mb-1">{{ $handover->handed_on->format('d.m.Y.') }} · {{ $handover->document_kind->label() }}
            · {{ $handover->person?->fullName() ?: 'organizacija' }}
            → {{ $handover->recipient }} ({{ $handover->purpose }})</p>
    @empty
        <p class="text-muted">U odabranom razdoblju nema zabilježenih predaja.</p>
    @endforelse
</div>
</body>
</html>
