@extends('layouts.organization')

@section('title', 'Zahtjevi')
@section('nav-suffix', 'Moje')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="page-heading mb-0">
        <h1>Zahtjevi</h1>
        @if($leave)
            <p class="text-muted mb-0">GO {{ $leave['year'] }}.: preostalo <strong>{{ $leave['remaining'] }}</strong> (staro {{ $leave['remaining_old'] }}, novo {{ $leave['remaining_new'] }})</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('organization.absences.calendar', $organization->slug) }}" class="btn btn-outline-secondary">Kalendar</a>
        <a href="{{ route('organization.requests.create', $organization->slug) }}" class="btn btn-primary">Novi zahtjev</a>
    </div>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Vrsta</th>
                    <th>Razdoblje</th>
                    <th>Opseg</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>{{ $item->type->label() }}@if($item->person && $item->person->user_id !== auth()->id()) <span class="text-muted small">· {{ $item->person->fullName() }}</span>@endif</td>
                        <td>{{ $item->fromDate() }}{{ $item->toDate() && $item->toDate() !== $item->fromDate() ? ' – '.$item->toDate() : '' }}</td>
                        <td>
                            @if($item->type === \App\Enums\RequestType::Overtime)
                                {{ $item->minutes() }} min
                            @elseif($item->type === \App\Enums\RequestType::PunchCorrection)
                                {{ \Carbon\Carbon::parse($item->payload['occurred_at'] ?? $item->fromDate())->format('d.m. H:i') }}
                            @elseif($item->type === \App\Enums\RequestType::PersonalDataChange)
                                {{ $item->changeCount() }} polja
                                @if($item->isLatePersonalData())
                                    <span class="badge text-bg-warning">kasnije od 8 d.</span>
                                @endif
                            @else
                                {{ $item->days() }}
                            @endif
                        </td>
                        <td><span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('organization.requests.show', [$organization->slug, $item]) }}" class="btn btn-outline-secondary btn-sm">Otvori</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema zahtjeva.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
