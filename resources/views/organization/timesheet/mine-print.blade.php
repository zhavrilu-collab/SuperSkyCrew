@extends('layouts.print')

@section('title', 'Uvid u evidenciju RV — '.$person->fullName())

@section('toolbar')
    <a href="{{ route('organization.timesheet.mine', [$organization->slug, 'from' => $from->toDateString()]) }}" class="small">← Moj tjedan</a>
    <button class="btn btn-primary" type="button" onclick="window.print()">Ispiši</button>
@endsection

@section('content')
    <h1 class="h4 mb-1">Uvid u evidenciju radnog vremena</h1>
    <p class="text-muted">Čl. 20. NN 55/2024 · {{ $person->fullName() }} · {{ $from->format('d.m.') }} – {{ $to->format('d.m.Y.') }}</p>

    <div class="table-responsive table-responsive-no-sticky">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>Dan</th>
                    <th>Plan</th>
                    <th>Početak</th>
                    <th>Završetak</th>
                    <th>Ukupno</th>
                    <th>Evidenc.</th>
                    <th>Prekovr.</th>
                    <th>Odsutnost</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($days as $day)
                    @php($entry = $entries->get($day->toDateString()))
                    @php($shift = $plan[$day->toDateString()] ?? null)
                    @php($weekday = [1 => 'ponedjeljak', 2 => 'utorak', 3 => 'srijeda', 4 => 'četvrtak', 5 => 'petak', 6 => 'subota', 7 => 'nedjelja'][$day->isoWeekday()])
                    <tr>
                        <td>{{ $weekday }} {{ $day->format('d.m.') }}</td>
                        <td>{{ $shift ? $shift->shortCode().' '.$shift->clockRange() : '—' }}</td>
                        <td>{{ $entry?->started_at?->timezone(config('app.timezone'))->format('H:i') ?: '—' }}</td>
                        <td>{{ $entry?->ended_at?->timezone(config('app.timezone'))->format('H:i') ?: '—' }}</td>
                        <td>{{ $entry ? number_format($entry->total_minutes / 60, 1).' h' : '—' }}</td>
                        <td>{{ $entry ? number_format($entry->evidential_minutes / 60, 1).' h'.($entry->evidential_code ? ' · '.$entry->evidential_code : '') : '—' }}</td>
                        <td>{{ $entry && $entry->overtime_minutes ? $entry->overtime_minutes.' min' : '—' }}</td>
                        <td>{{ $entry?->absence_code ?: '—' }}</td>
                        <td>{{ $entry?->status->label() ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted">Nema dana za prikaz.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="small text-muted mt-3 mb-0">Ispis za radnika. Korekcije i ručni unos vidi HR na šihterici.</p>
@endsection
