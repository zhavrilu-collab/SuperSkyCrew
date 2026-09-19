@extends('layouts.organization')

@section('title', 'Odobrenja')
@section('nav-suffix', 'Odobrenja')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="page-heading mb-0">
        <h1>Odobrenja</h1>
        <p class="text-muted mb-0">{{ $pending->count() }} zahtjeva čeka vašu odluku</p>
    </div>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Vrsta</th>
                    <th>Razdoblje</th>
                    <th>Dani</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pending as $item)
                    <tr>
                        <td>{{ $item->person->fullName() }}</td>
                        <td>{{ $item->type->label() }}</td>
                        <td>{{ $item->fromDate() }} – {{ $item->toDate() }}</td>
                        <td>{{ $item->days() }}</td>
                        <td class="text-end">
                            <a href="{{ route('organization.requests.show', [$organization->slug, $item]) }}" class="btn btn-primary btn-sm">Otvori</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema zahtjeva na čekanju.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
