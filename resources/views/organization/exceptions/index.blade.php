@extends('layouts.organization')

@section('title', 'Iznimke')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="page-heading mb-0">
        <h1>Queue iznimki</h1>
        <p class="text-muted mb-0">Nekompletni slogovi, kašnjenje, dnevni odmor, fond, rad na blagdanu ili u nedjelju. Današnje otvorene prijave nisu u redu.</p>
    </div>
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="date" class="form-control" name="from" value="{{ $from->toDateString() }}">
        <input type="date" class="form-control" name="to" value="{{ $to->toDateString() }}">
        <select class="form-select" name="code" onchange="this.form.submit()">
            <option value="">Sve iznimke</option>
            @foreach($exceptionCodes as $item)
                <option value="{{ $item->value }}" @selected($codeFilter === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        <input type="hidden" name="resolved" value="{{ $resolved ? 1 : 0 }}">
        <button class="btn btn-outline-secondary">Prikaži</button>
        @if($resolved)
            <a class="btn btn-outline-primary" href="{{ route('organization.exceptions.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'code' => $codeFilter]) }}">Otvorene</a>
        @else
            <a class="btn btn-outline-primary" href="{{ route('organization.exceptions.index', [$organization->slug, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'code' => $codeFilter, 'resolved' => 1]) }}">Riješene</a>
        @endif
    </form>
</div>

<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Datum</th>
                    <th>Iznimka</th>
                    <th>Sati</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->person?->fullName() ?: '—' }}</td>
                        <td>{{ $entry->work_date->format('d.m.Y.') }}</td>
                        <td>
                            <span class="badge {{ $entry->exception()?->badgeClass() ?? 'text-bg-secondary' }}">{{ $entry->exceptionLabel() }}</span>
                            @if($entry->exception_resolved_at)
                                <div class="small text-muted mt-1">
                                    {{ $entry->resolvedBy?->name }} · {{ $entry->exception_resolved_at->timezone(config('app.timezone'))->format('d.m. H:i') }}
                                    @if($entry->exception_note)
                                        · {{ $entry->exception_note }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ number_format($entry->total_minutes / 60, 1) }} h</td>
                        <td class="text-end">
                            @if($entry->person)
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.timesheet.day', [$organization->slug, $entry->person, $entry->work_date->toDateString()]) }}">Dan</a>
                            @endif
                            @if($canResolve && $entry->hasOpenException())
                                <form method="POST" action="{{ route('organization.exceptions.resolve', [$organization->slug, $entry]) }}" class="d-inline-flex gap-1 mt-1">
                                    @csrf
                                    <input class="form-control form-control-sm" name="comment" required placeholder="Napomena" maxlength="255" style="min-width: 12rem">
                                    <button class="btn btn-outline-success btn-sm" type="submit">Riješi</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted">{{ $resolved ? 'Nema riješenih iznimki u rasponu.' : 'Nema otvorenih iznimki.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
