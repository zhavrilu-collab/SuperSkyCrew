@extends('layouts.organization')

@section('title', 'Podaci za plaće')
@section('nav-suffix', 'Kadrovi')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Podaci za plaće</h1>
        <p class="text-muted mb-0">Čl. 3. st. 2. — unos, ne obračun. Stanje na {{ $on->format('d.m.Y.') }}.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2">
            <input type="date" class="form-control" name="na" value="{{ $on->toDateString() }}">
            <button class="btn btn-outline-secondary" type="submit">Na dan</button>
        </form>
        <a class="btn btn-outline-primary" href="{{ route('organization.people.payroll-export', [$organization->slug, 'na' => $on->toDateString()]) }}">Izvoz CSV</a>
    </div>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>IBAN</th>
                    <th>Koef.</th>
                    <th>Dodaci %</th>
                    <th>Staž prije</th>
                    <th>Djeca</th>
                    <th>Olakšica</th>
                    <th>Obiteljsko</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $person->fullName() }}</div>
                            <div class="text-muted small">{{ $person->jobLabel() }}{{ $person->costCenter ? ' · '.$person->costCenter->summary() : '' }}</div>
                        </td>
                        <td class="font-monospace">{{ $person->iban ?: '—' }}</td>
                        <td>{{ $person->pay_coefficient !== null ? $person->pay_coefficient : '—' }}</td>
                        <td>{{ $person->allowance_percent !== null ? $person->allowance_percent : '—' }}</td>
                        <td>{{ $person->priorServiceLabel() }}</td>
                        <td>{{ $person->children_count }}</td>
                        <td>{{ $person->tax_relief_note ?: ($person->dependents_count ? $person->dependents_count.' uzdržavanih' : '—') }}</td>
                        <td>{{ $person->family_right?->label() ?: '—' }}</td>
                        <td class="text-end">{!! $canEditPeople ? '<a class="btn btn-outline-secondary btn-sm" href="'.e(route('organization.people.edit', [$organization->slug, $person])).'">Kartica</a>' : '' !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-muted">Nema aktivnih osoba na taj dan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-3 mb-0">Računovodstvo vidi i izvozi ove podatke; izmjena je samo na kartici HR-a.</p>
@endsection
