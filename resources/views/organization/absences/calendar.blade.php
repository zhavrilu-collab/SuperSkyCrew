@extends('layouts.organization')

@section('title', 'Kalendar odsutnosti')

@section('content')
<style>
    .absence-cal { font-size: .75rem; }
    .absence-cal th, .absence-cal td { min-width: 2rem; padding: .3rem .15rem; text-align: center; vertical-align: middle; }
    .absence-cal th.person, .absence-cal td.person { min-width: 9rem; text-align: left; position: sticky; left: 0; background: #fff; z-index: 1; }
    .absence-cal .weekend { background: #f8f9fa; }
    .absence-cal .holiday { background: #eee; }
    .absence-cal .today { box-shadow: inset 0 0 0 2px #0d6efd; }
</style>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1">Kalendar odsutnosti</h1>
        <p class="text-muted mb-0">
            {{ $monthLabel }}
            · odobreno u šihterici, na odobrenju obrubljeno.
            @if($canViewTeam)
                Danas odsutnih: <strong>{{ $absentToday }}</strong>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('organization.absences.calendar', [$organization->slug, 'month' => $prev->format('Y-m'), 'code' => $codeFilter, 'department' => $departmentFilter]) }}">←</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.absences.calendar', [$organization->slug, 'month' => now()->format('Y-m'), 'code' => $codeFilter, 'department' => $departmentFilter]) }}">Ovaj mjesec</a>
        <a class="btn btn-outline-secondary" href="{{ route('organization.absences.calendar', [$organization->slug, 'month' => $next->format('Y-m'), 'code' => $codeFilter, 'department' => $departmentFilter]) }}">→</a>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            @if($canViewTeam && $departments->isNotEmpty())
                <select class="form-select" name="department" onchange="this.form.submit()">
                    <option value="">Svi odjeli</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) $departmentFilter === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            @endif
            <select class="form-select" name="code" onchange="this.form.submit()">
                <option value="">Sve šifre</option>
                @foreach($codes as $item)
                    <option value="{{ $item->code }}" @selected($codeFilter === $item->code)>{{ $item->code }} · {{ $item->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table table-bordered mb-0 absence-cal">
            <thead>
                <tr>
                    <th class="person">Osoba</th>
                    @foreach($days as $day)
                        @php($isWeekend = $day->isWeekend())
                        @php($isHoliday = \App\Support\CroatianHolidays::isHoliday($day))
                        <th class="{{ $isHoliday ? 'holiday' : ($isWeekend ? 'weekend' : '') }} {{ $day->toDateString() === $today ? 'today' : '' }}" title="{{ $day->format('d.m.Y.') }}">
                            <div>{{ $weekdays[$day->isoWeekday()] }}</div>
                            <div>{{ $day->format('j') }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td class="person">{{ $person->fullName() }}{{ $canViewTeam && $person->department ? ' · '.$person->department->name : '' }}</td>
                        @foreach($days as $day)
                            @php($key = $day->toDateString())
                            @php($cell = $cells[$person->id][$key] ?? null)
                            @php($isWeekend = $day->isWeekend())
                            @php($isHoliday = \App\Support\CroatianHolidays::isHoliday($day))
                            <td class="{{ $isHoliday ? 'holiday' : ($isWeekend ? 'weekend' : '') }} {{ $key === $today ? 'today' : '' }}">
                                @if($cell)
                                    @php($code = $codes->get($cell['code']))
                                    @if($cell['pending'] && $cell['request_id'])
                                        <a class="badge bg-white text-dark border border-warning text-decoration-none" href="{{ route('organization.requests.show', [$organization->slug, $cell['request_id']]) }}" title="{{ ($code->name ?? $cell['code']).' · na odobrenju' }}">{{ $cell['code'] }}</a>
                                    @else
                                        <a class="badge {{ $code?->badgeClass() ?? 'text-bg-secondary' }} text-decoration-none" href="{{ route('organization.timesheet.day', [$organization->slug, $person, $key]) }}" title="{{ $code->name ?? $cell['code'] }}">{{ $cell['code'] }}</a>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 1 }}" class="text-muted text-start p-3">Nema osoba za prikaz.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 small">
    @foreach($codes as $item)
        <span class="badge {{ $item->badgeClass() }}">{{ $item->code }} · {{ $item->name }}</span>
    @endforeach
    <span class="badge bg-white text-dark border border-warning">šifra · na odobrenju</span>
</div>
@endsection
