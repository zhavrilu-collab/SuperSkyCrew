@extends('layouts.organization')

@section('title', 'Dan u šihterici')
@section('nav-suffix', 'Vrijeme')

@section('content')
<div class="mb-4">
    @if($canOpenTimesheet ?? true)
        <a href="{{ route('organization.timesheet.index', $organization->slug) }}" class="small">← Šihterica</a>
    @else
        <a href="{{ route('organization.timesheet.mine', $organization->slug) }}" class="small">← Moj tjedan</a>
    @endif
    <h1 class="mt-2 mb-1">{{ $person->fullName() }} · {{ $day->format('d.m.Y.') }}</h1>
    <p class="text-muted">Plan: {{ $plannedShift?->label() ?: 'nema smjene' }}</p>
    @if($periodLocked)
        <p class="text-warning mb-0">Ovaj dan je zaključan. Ručni unos nije dopušten.</p>
    @endif
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="kartica-kontejner mb-4">
            <div class="card-header bg-white fw-semibold">Punchovi</div>
            <ul class="list-group list-group-flush">
                @forelse($punches as $punch)
                    <li class="list-group-item d-flex justify-content-between {{ $punch->corrections->isNotEmpty() ? 'text-decoration-line-through text-muted' : '' }}">
                        <span>
                            {{ $punch->type->label() }} · {{ $punch->channel->label() }}
                            @if($punch->isCorrection()) <span class="badge text-bg-info">ispravak</span> @endif
                            @if($punch->corrections->isNotEmpty()) <span class="badge text-bg-secondary">zamijenjeno</span> @endif
                            @if($punch->deviceMismatch()) <span class="badge text-bg-warning">drugi uređaj</span> @endif
                            @if($punch->hasPhoto())
                                <a class="small ms-1" href="{{ route('organization.timesheet.photo', [$organization->slug, $person, $punch]) }}">foto</a>
                            @endif
                        </span>
                        <span>{{ $punch->occurred_at_device->timezone(config('app.timezone'))->format('H:i:s') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Nema prijava za ovaj dan.</li>
                @endforelse
            </ul>
        </div>

        @if($canManual)
        <div class="kartica-kontejner">
            <div class="card-header bg-white fw-semibold">Ručni unos</div>
            <div class="card-body">
                <form method="POST" action="{{ route('organization.timesheet.manual', [$organization->slug, $person]) }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label" for="occurred_at">Vrijeme</label>
                        <input class="form-control" type="datetime-local" name="occurred_at" id="occurred_at" value="{{ $day->format('Y-m-d') }}T08:00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="type">Vrsta</label>
                        <select class="form-select" name="type" id="type">
                            <option value="in">Prijava</option>
                            <option value="out">Odjava</option>
                            <option value="break_start">Početak pauze</option>
                            <option value="break_end">Kraj pauze</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="reason">Razlog</label>
                        <input class="form-control" name="reason" id="reason" required placeholder="npr. zaboravljena odjava">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Spremi punch</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-5">
        <div class="kartica-kontejner">
            <div class="card-header bg-white fw-semibold">Dnevni slog (čl. 13.)</div>
            <div class="card-body">
                @if($entry)
                    <dl class="row mb-0">
                        <dt class="col-7">Plan</dt>
                        <dd class="col-5">{{ $entry->plannedShift?->clockRange() ?: ($plannedShift?->clockRange() ?: '—') }}</dd>
                        <dt class="col-7">Početak</dt><dd class="col-5">{{ $entry->started_at?->timezone(config('app.timezone'))->format('H:i') ?: '—' }}</dd>
                        <dt class="col-7">Završetak</dt><dd class="col-5">{{ $entry->ended_at?->timezone(config('app.timezone'))->format('H:i') ?: '—' }}</dd>
                        <dt class="col-7">Ukupno (realizacija)</dt><dd class="col-5">{{ number_format($entry->total_minutes / 60, 2) }} h</dd>
                        <dt class="col-7">Pauza</dt><dd class="col-5">{{ $entry->break_minutes }} min</dd>
                        <dt class="col-7">Noć</dt><dd class="col-5">{{ $entry->night_minutes }} min</dd>
                        <dt class="col-7">Prekovremeni</dt><dd class="col-5">{{ $entry->overtime_minutes }} min</dd>
                        <dt class="col-7">Nedjelja</dt><dd class="col-5">{{ $entry->sunday_minutes }} min</dd>
                        <dt class="col-7">Blagdan</dt><dd class="col-5">{{ $entry->holiday_minutes }} min</dd>
                        <dt class="col-7">Evidencijski</dt>
                        <dd class="col-5">
                            {{ number_format($entry->evidential_minutes / 60, 2) }} h
                            {{ $entry->evidential_code ? ' · '.$entry->evidential_code : '' }}
                            {{ $entry->evidential_manual ? ' · ručno' : '' }}
                        </dd>
                        <dt class="col-7">Mjesto troška</dt>
                        <dd class="col-5">{{ $entry->evidentialCostCenter?->summary() ?: ($person->costCenter?->summary() ?: '—') }}</dd>
                        <dt class="col-7">Odsutnost</dt><dd class="col-5">{{ $entry->absence_code ? $entry->absence_code.' ('.$entry->absence_minutes.' min)' : '—' }}</dd>
                        <dt class="col-7">Status</dt><dd class="col-5">{{ $entry->status->label() }}</dd>
                        <dt class="col-7">Iznimka</dt>
                        <dd class="col-5">
                            @if($entry->exception_code)
                                <span class="badge {{ $entry->exception()?->badgeClass() ?? 'text-bg-secondary' }}">{{ $entry->exceptionLabel() }}</span>
                                @if($entry->exception_resolved_at)
                                    <div class="small text-muted">riješeno</div>
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                    @if($canResolve ?? false)
                        <form method="POST" action="{{ route('organization.exceptions.resolve', [$organization->slug, $entry]) }}" class="mt-3">
                            @csrf
                            <label class="form-label" for="comment">Riješi iznimku</label>
                            <div class="d-flex gap-2">
                                <input class="form-control" name="comment" id="comment" required maxlength="255" placeholder="Napomena">
                                <button class="btn btn-outline-success" type="submit">Riješi</button>
                            </div>
                        </form>
                    @endif
                @else
                    <p class="text-muted mb-0">Nema sloga za ovaj dan.</p>
                @endif
            </div>
        </div>

        @if($canEditEvidential ?? false)
        <div class="kartica-kontejner mt-4">
            <div class="card-header bg-white fw-semibold">Evidencijski sati (plaća)</div>
            <div class="card-body">
                <p class="small text-muted">Realizacija ostaje iz puncha. HR korekcija ide u izvoz za plaće (šifra × sati × MT) i ostaje nakon ponovnog izračuna.</p>
                <form method="POST" action="{{ route('organization.timesheet.evidential', [$organization->slug, $person, $day->toDateString()]) }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label" for="evidential_minutes">Minute</label>
                        <input type="number" min="0" max="1440" class="form-control" name="evidential_minutes" id="evidential_minutes" required value="{{ old('evidential_minutes', $entry?->evidential_minutes ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="evidential_code">Šifra</label>
                        <select class="form-select" name="evidential_code" id="evidential_code" required>
                            @foreach($codes as $code)
                                <option value="{{ $code->code }}" @selected((string) old('evidential_code', $entry?->evidential_code ?: ($entry?->absence_code ?: 'RD')) === $code->code)>{{ $code->code }} · {{ $code->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="evidential_cost_center_id">Mjesto troška</label>
                        <select class="form-select" name="evidential_cost_center_id" id="evidential_cost_center_id">
                            <option value="">—</option>
                            @foreach($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}" @selected((string) old('evidential_cost_center_id', $entry?->evidential_cost_center_id ?: $person->cost_center_id) === (string) $costCenter->id)>{{ $costCenter->summary() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="evidential_note">Razlog</label>
                        <input class="form-control" name="evidential_note" id="evidential_note" required maxlength="255" value="{{ old('evidential_note', $entry?->evidential_note) }}" placeholder="npr. zaokruživanje na puni sat">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Spremi evidenciju</button>
                    </div>
                </form>
                @if($entry?->evidential_manual)
                    <form method="POST" action="{{ route('organization.timesheet.evidential', [$organization->slug, $person, $day->toDateString()]) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="revert" value="1">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Vrati na realizaciju</button>
                    </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
