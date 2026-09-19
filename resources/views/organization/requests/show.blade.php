@extends('layouts.organization')

@section('title', 'Zahtjev')
@section('nav-suffix', 'Moje')

@section('content')
<div class="mb-4">
    <a href="{{ route('organization.requests.index', $organization->slug) }}" class="small">← Zahtjevi</a>
    <h1 class="mt-2 mb-1">{{ $requestItem->type->label() }} · {{ $requestItem->person->fullName() }}</h1>
    <span class="badge {{ $requestItem->status->badgeClass() }}">{{ $requestItem->status->label() }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="kartica-kontejner mb-4">
            <div class="card-body">
                <dl class="row mb-0">
                    @if($requestItem->type === \App\Enums\RequestType::PunchCorrection)
                        <dt class="col-4">Izvorno</dt>
                        <dd class="col-8">{{ ($requestItem->payload['original_type'] ?? '') === 'out' ? 'Odjava' : (($requestItem->payload['original_type'] ?? '') === 'in' ? 'Prijava' : ($requestItem->payload['original_type'] ?? '—')) }}
                            · {{ $requestItem->payload['original_occurred_at'] ?? '—' }}</dd>
                        <dt class="col-4">Novo vrijeme</dt>
                        <dd class="col-8">{{ $requestItem->payload['occurred_at'] ?? '—' }}</dd>
                    @elseif($requestItem->type === \App\Enums\RequestType::Overtime)
                        <dt class="col-4">Datum</dt><dd class="col-8">{{ $requestItem->fromDate() }}</dd>
                        <dt class="col-4">Minute</dt><dd class="col-8">{{ $requestItem->minutes() ?? '—' }}</dd>
                    @elseif($requestItem->type === \App\Enums\RequestType::PersonalDataChange)
                        <dt class="col-4">Nastanak</dt>
                        <dd class="col-8">{{ $requestItem->fromDate() }}</dd>
                        @if($requestItem->isLatePersonalData())
                            <dt class="col-4">Rok</dt>
                            <dd class="col-8"><span class="text-warning">Prijavljeno nakon 8 dana (čl. 5. st. 2.).</span></dd>
                        @endif
                        <dt class="col-4">Promjene</dt>
                        <dd class="col-8">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Polje</th><th>Prije</th><th>Poslije</th></tr></thead>
                                <tbody>
                                    @foreach($requestItem->payload['changes'] ?? [] as $field => $change)
                                        <tr>
                                            <td>{{ \App\Support\PersonalDataChange::FIELDS[$field] ?? $field }}</td>
                                            <td>{{ \App\Support\PersonalDataChange::display($field, $change['from'] ?? null) }}</td>
                                            <td>{{ \App\Support\PersonalDataChange::display($field, $change['to'] ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </dd>
                    @else
                        <dt class="col-4">Od – do</dt><dd class="col-8">{{ $requestItem->fromDate() }} – {{ $requestItem->toDate() }}</dd>
                        <dt class="col-4">Radni dani</dt><dd class="col-8">{{ $requestItem->days() }}</dd>
                        <dt class="col-4">Šifra</dt><dd class="col-8">{{ $requestItem->absenceCode() ?: '—' }}</dd>
                    @endif
                    <dt class="col-4">Napomena</dt><dd class="col-8">{{ $requestItem->payload['note'] ?: '—' }}</dd>
                    @if($requestItem->current_role)
                        <dt class="col-4">Čeka</dt><dd class="col-8">{{ $requestItem->current_role === 'manager' ? 'Voditelja' : 'HR' }}</dd>
                    @endif
                </dl>
                @if($requestItem->hasLeaveDecision())
                    <a class="btn btn-outline-primary mt-3" href="{{ route('organization.requests.decision', [$organization->slug, $requestItem]) }}">Rješenje o GO</a>
                    <p class="small text-muted mt-2 mb-0">Word rješenje upisano je u dosje osobe.</p>
                @endif
            </div>
        </div>

        @if($canCancel)
            <form method="POST" action="{{ route('organization.requests.cancel', [$organization->slug, $requestItem]) }}">
                @csrf
                <button class="btn btn-outline-secondary" type="submit">Poništi zahtjev</button>
            </form>
        @endif

        @if($canAct)
            <div class="kartica-kontejner mt-4">
                <div class="card-header bg-white fw-semibold">Odluka</div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" action="{{ route('organization.approvals.approve', [$organization->slug, $requestItem]) }}" class="mb-3">
                        @csrf
                        <button class="btn btn-success" type="submit">Odobri</button>
                    </form>
                    <form method="POST" action="{{ route('organization.approvals.reject', [$organization->slug, $requestItem]) }}" class="row g-2">
                        @csrf
                        <div class="col-md-8">
                            <input class="form-control" name="comment" required placeholder="Razlog odbijanja">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-danger" type="submit">Odbij</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-5">
        <div class="kartica-kontejner">
            <div class="card-header bg-white fw-semibold">Povijest</div>
            <ul class="list-group list-group-flush">
                @forelse($requestItem->actions as $action)
                    <li class="list-group-item">
                        <div class="fw-semibold">{{ $action->action->label() }}</div>
                        <div class="small text-muted">{{ $action->user?->name }} · {{ $action->created_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
                        @if($action->comment)
                            <div class="small">{{ $action->comment }}</div>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">Nema zapisa.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
