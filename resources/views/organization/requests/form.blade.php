@extends('layouts.organization')

@section('title', 'Novi zahtjev')

@section('content')
<h1 class="h4 mb-1">Novi zahtjev</h1>
<p class="text-muted mb-4">{{ $person->fullName() }} · GO preostalo {{ $leave['remaining'] }} dana (staro {{ $leave['remaining_old'] }} / novo {{ $leave['remaining_new'] }})</p>

<form method="POST" action="{{ route('organization.requests.store', $organization->slug) }}" class="card border-0 shadow-sm" id="request-form">
    @csrf
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="type">Vrsta</label>
                @php
                    $defaultType = old('type', $selectedType ?: ($selectedPunchId ? 'punch_correction' : 'leave_annual'));
                @endphp
                <select class="form-select" name="type" id="type" required>
                    <option value="leave_annual" @selected($defaultType === 'leave_annual')>Godišnji odmor</option>
                    <option value="leave_other" @selected($defaultType === 'leave_other')>Druga odsutnost</option>
                    <option value="overtime" @selected($defaultType === 'overtime')>Prekovremeni</option>
                    <option value="punch_correction" @selected($defaultType === 'punch_correction')>Ispravak prijave</option>
                    <option value="personal_data" @selected($defaultType === 'personal_data')>Promjena podataka</option>
                </select>
            </div>
            <div class="col-md-4 js-from">
                <label class="form-label" for="from">Od / datum</label>
                <input type="date" class="form-control" name="from" id="from" value="{{ old('from') }}">
            </div>
            <div class="col-md-4 js-leave">
                <label class="form-label" for="to">Do</label>
                <input type="date" class="form-control" name="to" id="to" value="{{ old('to') }}">
            </div>
            <div class="col-md-4 js-overtime">
                <label class="form-label" for="minutes">Minute prekovremenog</label>
                <input type="number" min="15" max="720" step="15" class="form-control" name="minutes" id="minutes" value="{{ old('minutes', 60) }}">
            </div>
            <div class="col-md-4 js-leave-other">
                <label class="form-label" for="absence_code">Šifra odsutnosti</label>
                <select class="form-select" name="absence_code" id="absence_code">
                    <option value="">—</option>
                    @foreach($absenceCodes as $code)
                        <option value="{{ $code->code }}" @selected(old('absence_code') === $code->code)>{{ $code->code }} · {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 js-correction">
                <label class="form-label" for="punch_id">Prijava za ispravak</label>
                <select class="form-select" name="punch_id" id="punch_id">
                    <option value="">—</option>
                    @foreach($punches as $punch)
                        <option value="{{ $punch->id }}" @selected((string) old('punch_id', $selectedPunchId) === (string) $punch->id)>
                            {{ $punch->occurred_at_device->timezone(config('app.timezone'))->format('d.m.Y. H:i') }} · {{ $punch->type->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 js-correction">
                <label class="form-label" for="occurred_at">Točno vrijeme</label>
                <input type="datetime-local" class="form-control" name="occurred_at" id="occurred_at" value="{{ old('occurred_at') }}">
            </div>
            <div class="col-md-4 js-data">
                <label class="form-label" for="occurred_on">Datum nastanka</label>
                <input type="date" class="form-control" name="occurred_on" id="occurred_on" value="{{ old('occurred_on') }}" max="{{ now()->toDateString() }}">
                <div class="form-text">Rok prijave je 8 dana od nastanka (čl. 5. st. 2.).</div>
            </div>
            <div class="col-md-4 js-data">
                <label class="form-label" for="first_name">Ime</label>
                <input class="form-control" name="first_name" id="first_name" value="{{ old('first_name', $currentData['first_name'] ?? '') }}">
            </div>
            <div class="col-md-4 js-data">
                <label class="form-label" for="last_name">Prezime</label>
                <input class="form-control" name="last_name" id="last_name" value="{{ old('last_name', $currentData['last_name'] ?? '') }}">
            </div>
            <div class="col-md-4 js-data">
                <label class="form-label" for="oib">OIB</label>
                <input class="form-control" name="oib" id="oib" value="{{ old('oib', $currentData['oib'] ?? '') }}" maxlength="11">
            </div>
            <div class="col-md-4 js-data">
                <label class="form-label" for="gender">Spol</label>
                <select class="form-select" name="gender" id="gender">
                    <option value="">—</option>
                    <option value="m" @selected(old('gender', $currentData['gender'] ?? '') === 'm')>M</option>
                    <option value="z" @selected(old('gender', $currentData['gender'] ?? '') === 'z')>Ž</option>
                    <option value="x" @selected(old('gender', $currentData['gender'] ?? '') === 'x')>Ostalo</option>
                </select>
            </div>
            <div class="col-md-4 js-data">
                <label class="form-label" for="date_of_birth">Datum rođenja</label>
                <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $currentData['date_of_birth'] ?? '') }}">
            </div>
            <div class="col-md-6 js-data">
                <label class="form-label" for="citizenship">Državljanstvo</label>
                <input class="form-control" name="citizenship" id="citizenship" value="{{ old('citizenship', $currentData['citizenship'] ?? '') }}">
            </div>
            <div class="col-12 js-data">
                <label class="form-label" for="residence">Prebivalište / boravište</label>
                <input class="form-control" name="residence" id="residence" value="{{ old('residence', $currentData['residence'] ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="note">Napomena</label>
                <input class="form-control" name="note" id="note" value="{{ old('note') }}" maxlength="255">
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-primary" type="submit">Pošalji na odobrenje</button>
        <a class="btn btn-outline-secondary" href="{{ route('organization.requests.index', $organization->slug) }}">Odustani</a>
    </div>
</form>
<script>
    function toggleRequestFields() {
        var type = document.getElementById('type').value;
        document.querySelectorAll('.js-from').forEach(function (el) { el.classList.toggle('d-none', type !== 'leave_annual' && type !== 'leave_other' && type !== 'overtime'); });
        document.querySelectorAll('.js-leave').forEach(function (el) { el.classList.toggle('d-none', type !== 'leave_annual' && type !== 'leave_other'); });
        document.querySelectorAll('.js-leave-other').forEach(function (el) { el.classList.toggle('d-none', type !== 'leave_other'); });
        document.querySelectorAll('.js-overtime').forEach(function (el) { el.classList.toggle('d-none', type !== 'overtime'); });
        document.querySelectorAll('.js-correction').forEach(function (el) { el.classList.toggle('d-none', type !== 'punch_correction'); });
        document.querySelectorAll('.js-data').forEach(function (el) { el.classList.toggle('d-none', type !== 'personal_data'); });
    }
    document.getElementById('type').addEventListener('change', toggleRequestFields);
    toggleRequestFields();
</script>
@endsection
