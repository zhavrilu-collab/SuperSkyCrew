@extends('layouts.organization')

@section('title', 'Raspored')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Raspored i smjene</h1>
        <p class="text-muted mb-0">Četiri razine: radnik, radno mjesto, odjel, organizacija. Specifičnija pobjeđuje. Prijava izvan plana se ne odbija; kašnjenje ({{ \App\Services\ShiftResolver::LATE_GRACE_MINUTES }} min) ide u iznimke.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', [$organization->slug, 'from' => $prev->toDateString()]) }}">←</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', $organization->slug) }}">Ovaj tjedan</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.schedule.index', [$organization->slug, 'from' => $next->toDateString()]) }}">→</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6">Plan tjedna {{ $from->format('d.m.') }} – {{ $to->format('d.m.Y.') }}</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Osoba</th>
                        @foreach($days as $day)
                            <th class="text-center {{ $day->isSunday() ? 'text-danger' : '' }}">{{ $weekdays[$day->isoWeekday()] }}<div class="small fw-normal">{{ $day->format('d.m.') }}</div></th>
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
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Smjene</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Naziv</th>
                                <th>Vrijeme</th>
                                <th>Pauza</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shifts as $shift)
                                <tr>
                                    <td>{{ $shift->code ? $shift->code.' · ' : '' }}{{ $shift->name }}{{ $shift->is_night ? ' · noć' : '' }}</td>
                                    <td>{{ $shift->clockRange() }}</td>
                                    <td>{{ $shift->break_minutes }} min</td>
                                    <td class="text-end">
                                        @if($canManage)
                                            <form method="POST" action="{{ route('organization.schedule.shifts.destroy', [$organization->slug, $shift]) }}" onsubmit="return confirm('Obrisati smjenu?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">Nema smjena.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Pravila kalendara</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Razina</th>
                                <th>Opseg</th>
                                <th>Dan</th>
                                <th>Smjena</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rules as $rule)
                                <tr>
                                    <td>{{ $rule->level->label() }}</td>
                                    <td>{{ $rule->scopeLabel() }}</td>
                                    <td>{{ $rule->weekdayLabel() }}</td>
                                    <td>{{ $rule->shift?->label() ?: 'slobodan dan' }}</td>
                                    <td class="text-end">
                                        @if($canManage)
                                            <form method="POST" action="{{ route('organization.schedule.rules.destroy', [$organization->slug, $rule]) }}" onsubmit="return confirm('Obrisati pravilo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">Nema pravila. Dodajte tjedni kalendar organizacije.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canManage)
<div class="row g-3">
    <div class="col-lg-5">
        <form method="POST" action="{{ route('organization.schedule.shifts.store', $organization->slug) }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body">
                <h2 class="h6">Nova smjena</h2>
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label" for="shift_name">Naziv</label>
                        <input class="form-control" name="name" id="shift_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shift_code">Oznaka</label>
                        <input class="form-control" name="code" id="shift_code" placeholder="P1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="starts_at">Početak</label>
                        <input type="time" class="form-control" name="starts_at" id="starts_at" value="08:00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="ends_at">Kraj</label>
                        <input type="time" class="form-control" name="ends_at" id="ends_at" value="16:00" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="break_minutes">Pauza (min)</label>
                        <input type="number" min="0" max="240" class="form-control" name="break_minutes" id="break_minutes" value="30">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_night" id="is_night" value="1">
                            <label class="form-check-label" for="is_night">Noćna smjena</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-primary" type="submit">Spremi smjenu</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <form method="POST" action="{{ route('organization.schedule.rules.store', $organization->slug) }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body">
                <h2 class="h6">Novo pravilo</h2>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label" for="level">Razina</label>
                        <select class="form-select" name="level" id="level" required>
                            @foreach($levels as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="weekday">Dan</label>
                        <select class="form-select" name="weekday" id="weekday" required>
                            @foreach($weekdayNames as $iso => $name)
                                <option value="{{ $iso }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shift_id">Smjena</label>
                        <select class="form-select" name="shift_id" id="shift_id">
                            <option value="">slobodan dan</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="department_id">Odjel</label>
                        <select class="form-select" name="department_id" id="department_id">
                            <option value="">—</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="job_position_id">Radno mjesto</label>
                        <select class="form-select" name="job_position_id" id="job_position_id">
                            <option value="">—</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="person_id">Radnik</label>
                        <select class="form-select" name="person_id" id="person_id">
                            <option value="">—</option>
                            @foreach($people as $person)
                                <option value="{{ $person->id }}">{{ $person->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="valid_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="valid_from" value="{{ $from->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="valid_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="valid_to">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-primary" type="submit">Spremi pravilo</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
