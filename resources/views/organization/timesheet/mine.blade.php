@extends('layouts.organization')

@section('title', 'Moj tjedan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Moj tjedan</h1>
        <p class="text-muted mb-0">{{ $person->fullName() }} · {{ $from->format('d.m.') }} – {{ $to->format('d.m.Y.') }} · uvid u evidenciju RV (čl. 20.)</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('organization.timesheet.mine', [$organization->slug, 'from' => $prev->toDateString()]) }}">← Prethodni</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.timesheet.mine', [$organization->slug, 'from' => $next->toDateString()]) }}">Sljedeći →</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Dan</th>
                    <th>Plan</th>
                    <th>Početak</th>
                    <th>Završetak</th>
                    <th>Ukupno</th>
                    <th>Prekovr.</th>
                    <th>Odsutnost</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $day)
                    @php($entry = $entries->get($day->toDateString()))
                    @php($shift = $plan[$day->toDateString()] ?? null)
                    @php($weekday = [1 => 'ponedjeljak', 2 => 'utorak', 3 => 'srijeda', 4 => 'četvrtak', 5 => 'petak', 6 => 'subota', 7 => 'nedjelja'][$day->isoWeekday()])
                    <tr>
                        <td>
                            <a href="{{ route('organization.timesheet.day', [$organization->slug, $person, $day->toDateString()]) }}">
                                {{ $weekday }}
                                <span class="text-muted">{{ $day->format('d.m.') }}</span>
                            </a>
                        </td>
                        <td>{{ $shift ? $shift->shortCode().' '.$shift->clockRange() : '—' }}</td>
                        <td>{{ $entry?->started_at?->timezone(config('app.timezone'))->format('H:i') ?: '—' }}</td>
                        <td>{{ $entry?->ended_at?->timezone(config('app.timezone'))->format('H:i') ?: '—' }}</td>
                        <td>
                            @if($entry)
                                {{ number_format($entry->total_minutes / 60, 1) }} h
                                @if($entry->exception_code)
                                    <span class="text-danger">!</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $entry && $entry->overtime_minutes ? $entry->overtime_minutes.' min' : '—' }}</td>
                        <td>{{ $entry?->absence_code ?: '—' }}</td>
                        <td>{{ $entry?->status->label() ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
