@extends('layouts.organization')

@section('title', 'Isteci dokumenata')

@section('content')
<div class="mb-4">
    <h1 class="h4 mb-1">Upozorenja isteka</h1>
    <p class="text-muted mb-1">UOR na određeno, probni rad, dozvola, liječnički, certifikat i kvalifikacije u sljedećih 30 dana (pragovi 5 / 10 / 20 / 30).</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Vrsta</th>
                    <th>Datum</th>
                    <th>Rok</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item['person']->fullName() }}</td>
                        <td>{{ $item['kind']->label() }}@if(!empty($item['detail'])) · {{ $item['detail'] }}@endif</td>
                        <td>{{ $item['date']->format('d.m.Y.') }}</td>
                        <td>
                            @if($item['overdue'])
                                <span class="badge text-bg-danger">isteklo {{ abs($item['days']) }} d.</span>
                            @elseif($item['window'] <= 5)
                                <span class="badge text-bg-danger">{{ $item['days'] }} d.</span>
                            @elseif($item['window'] <= 10)
                                <span class="badge text-bg-warning">{{ $item['days'] }} d.</span>
                            @else
                                <span class="badge text-bg-secondary">{{ $item['days'] }} d.</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.people.edit', [$organization->slug, $item['person']]) }}">Kartica</a>
                            @if($item['kind'] === \App\Enums\ExpiryKind::Medical)
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.people.referral', [$organization->slug, $item['person']]) }}">Uputnica</a>
                            @endif
                            @if(in_array($item['kind'], [\App\Enums\ExpiryKind::FixedTerm, \App\Enums\ExpiryKind::Trial], true))
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.people.contract', [$organization->slug, $item['person']]) }}">UOR</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted">Nema isteka u sljedećih 30 dana.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
