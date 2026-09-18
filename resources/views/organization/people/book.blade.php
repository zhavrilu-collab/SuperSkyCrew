<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Matična knjiga — {{ $organization->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            a { text-decoration: none; color: inherit; }
            table { font-size: .8rem; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2 no-print">
        <a href="{{ route('organization.people.index', $organization->slug) }}" class="small">← Kadar</a>
        <form method="GET" class="d-flex gap-2">
            <label class="form-label mb-0 align-self-center" for="na">Stanje na dan</label>
            <input type="date" class="form-control" name="na" id="na" value="{{ $on->toDateString() }}">
            <button class="btn btn-outline-secondary" type="submit">Prikaži</button>
        </form>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('organization.people.export', [$organization->slug, 'na' => $on->toDateString()]) }}">Izvoz CSV</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
        </div>
    </div>

    <h1 class="h4 mb-1">Matična knjiga radnika</h1>
    <p class="text-muted">{{ $organization->name }}{{ $organization->oib ? ' · OIB '.$organization->oib : '' }} · stanje na {{ $on->format('d.m.Y.') }} · {{ $people->count() }} aktivnih</p>
    <p class="small text-muted">Registar aktivnih osoba (čl. 3. / čl. 10.) na odabrani dan. Kandidati i volonteri nisu u knjizi.</p>

    <div class="table-responsive">
        <table class="table table-sm table-bordered bg-white">
            <thead>
                <tr>
                    <th>Rbr</th>
                    <th>Prezime i ime</th>
                    <th>OIB</th>
                    <th>Spol</th>
                    <th>Rođenje</th>
                    <th>Državljanstvo</th>
                    <th>Status</th>
                    <th>Radno mjesto</th>
                    <th>Odjel</th>
                    <th>Ugovor</th>
                    <th>Početak</th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $index => $person)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $person->last_name }} {{ $person->first_name }}</td>
                        <td>{{ $person->oib ?: '—' }}</td>
                        <td>{{ $person->genderLabel() }}</td>
                        <td>{{ $person->date_of_birth?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $person->citizenship ?: '—' }}</td>
                        <td>{{ $person->status->label() }}</td>
                        <td>{{ $person->jobLabel() }}</td>
                        <td>{{ $person->department?->name ?: '—' }}</td>
                        <td>{{ $person->contract_type?->label() ?: '—' }}</td>
                        <td>{{ $person->started_at?->format('d.m.Y.') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-muted">Nema aktivnih osoba na taj dan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3">Ispisao {{ $exporter->name }} · {{ $exportedAt->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}. Nije preslika osobne iskaznice.</p>
</div>
</body>
</html>
