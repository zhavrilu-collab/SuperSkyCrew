@extends('layouts.organization')

@section('title', 'Isteci dokumenata')
@section('nav-suffix', 'Zaposlenici')

@section('content')
<div class="page-heading">
    <h1>Upozorenja isteka</h1>
    <p class="text-muted mb-0">UOR na određeno, probni rad, dozvola, liječnički, certifikat, kvalifikacije i dokumenti dosjea u sljedećih {{ $horizon }} dana (pragovi 5 / 10 / 20 / 30).</p>
</div>

<div class="kartica-kontejner">
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
                        <td colspan="5" class="text-muted">Nema isteka u sljedećih {{ $horizon }} dana.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
