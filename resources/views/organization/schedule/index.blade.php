@extends('layouts.organization')

@section('title', 'Raspored')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Raspored i smjene</h1>
        <p class="text-muted mb-0">Tjedni plan. Smjene i kalendari uređuju se u Postavkama. Kašnjenje ({{ \App\Services\ShiftResolver::LATE_GRACE_MINUTES }} min) ide u iznimke.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', [$organization->slug, 'from' => $prev->toDateString()]) }}">←</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', $organization->slug) }}">Ovaj tjedan</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', [$organization->slug, 'from' => $next->toDateString()]) }}">→</a>
        @if($canManage)
            <a class="btn btn-outline-primary" href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'vrijeme', 'section' => 'smjene']) }}">Postavke smjena</a>
        @endif
        @if($canSend)
            <form method="POST" action="{{ route('organization.schedule.plan.send', $organization->slug) }}" class="d-inline">
                @csrf
                <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                <button class="btn btn-primary" type="submit">Pošalji plan e-mailom</button>
            </form>
        @endif
    </div>
</div>

<div class="kartica-kontejner">
    <h2 class="h6">Plan tjedna {{ $from->format('d.m.') }} – {{ $to->format('d.m.Y.') }}</h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Osoba</th>
                    @foreach($days as $day)
                        <th class="text-center {{ $day->isSunday() ? 'nedjelja' : '' }}">{{ $weekdays[$day->isoWeekday()] }}<div class="small fw-normal">{{ $day->format('d.m.') }}</div></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>{{ $person->fullName() }}{{ $person->department ? ' · '.$person->department->name : '' }}</td>
                        @foreach($days as $day)
                            @php($shift = $plan[$person->id][$day->toDateString()] ?? null)
                            <td class="text-center small">{{ $shift ? $shift->shortCode() : '·' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 1 }}" class="text-muted">Nema osoba za prikaz.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="small text-muted mb-0 mt-2">Šihterica prazne ćelije prikazuje istim kodom smjene (plan → šihterica). Blagdani RH su neredovni osim ako radnik ima vlastito pravilo.</p>
</div>
@endsection
