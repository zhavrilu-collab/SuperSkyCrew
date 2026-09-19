@extends('layouts.print')

@section('title', 'Evidencija radnog vremena — '.$organization->name)
@section('document-class', 'ispis-dokument--wide')

@section('toolbar')
    <a href="{{ route('organization.timesheet.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="small">← Šihterica</a>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('organization.handovers.index', $organization->slug) }}">Predaja (čl. 5.)</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.inspection-export', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Excel (CSV)</a>
        <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
    </div>
@endsection

@section('content')
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

    <h2 class="h6">Zbirno po osobi</h2>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Dani</th>
                    <th>Ukupno h</th>
                    <th>Evid. h</th>
                    <th>Zastoj</th>
                    <th>Teren</th>
                    <th>Pripr.</th>
                    <th>Dvokr.</th>
                    <th>Smjen.</th>
                    <th>Noć</th>
                    <th>Prekovr.</th>
                    <th>Nedj.</th>
                    <th>Blagdan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaries as $summary)
                    <tr>
                        <td>{{ $summary['name'] }}</td>
                        <td>{{ $summary['days'] }}</td>
                        <td>{{ number_format($summary['total_minutes'] / 60, 1) }}</td>
                        <td>{{ number_format($summary['evidential_minutes'] / 60, 1) }}</td>
                        <td>{{ $summary['downtime_minutes'] }}</td>
                        <td>{{ $summary['field_work_minutes'] }}</td>
                        <td>{{ $summary['standby_minutes'] }}</td>
                        <td>{{ $summary['split_shift_minutes'] }}</td>
                        <td>{{ $summary['shift_minutes'] }}</td>
                        <td>{{ $summary['night_minutes'] }}</td>
                        <td>{{ $summary['overtime_minutes'] }}</td>
                        <td>{{ $summary['sunday_minutes'] }}</td>
                        <td>{{ $summary['holiday_minutes'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-muted">Nema slogova u odabranom razdoblju.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h6">Dnevni slog</h2>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Datum</th>
                    @if($showBounds)
                        <th>Početak</th>
                        <th>Završetak</th>
                    @endif
                    <th>Ukupno min</th>
                    <th>Pauza</th>
                    <th>Zastoj</th>
                    <th>Teren</th>
                    <th>Pripr.</th>
                    <th>Dvokr.</th>
                    <th>Smjen.</th>
                    <th>Noć</th>
                    <th>Prekovr.</th>
                    <th>Nedj.</th>
                    <th>Blagdan</th>
                    <th>Odsutnost</th>
                    <th>Ods. min</th>
                    <th>Šifra</th>
                    <th>Evid. min</th>
                    <th>MT</th>
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
                        <td colspan="{{ $showBounds ? 22 : 20 }}" class="text-muted">Nema slogova u odabranom razdoblju.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h6">Značenje kratica (čl. 18. st. 2.)</h2>
    <dl class="row">
        @forelse($codes as $code)
            <dt class="col-sm-2">{{ $code->code }}</dt>
            <dd class="col-sm-10">{{ $code->legendLine() }}</dd>
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
        <p class="text-muted mb-0">U odabranom razdoblju nema zabilježenih predaja.</p>
    @endforelse
@endsection
