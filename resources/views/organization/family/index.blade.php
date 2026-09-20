@extends('layouts.organization')

@section('title', 'Članovi obitelji')
@section('nav-suffix', 'Zaposlenici')

@section('content')
<div class="page-heading">
    <h1>Članovi obitelji</h1>
    <p class="text-muted mb-0">Djeca i uzdržavani članovi ulaze u fond GO i evidenciju olakšica.</p>
</div>

<div class="kartica-kontejner mb-4">
    <form method="POST" action="{{ route('organization.family.store', $organization->slug) }}" class="row g-3">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="person_id">Zaposlenik</label>
            <select class="form-select" name="person_id" id="person_id" required>
                <option value="">—</option>
                @foreach($people as $person)
                    <option value="{{ $person->id }}">{{ $person->fullName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="first_name">Ime</label>
            <input class="form-control" name="first_name" id="first_name" required>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="last_name">Prezime</label>
            <input class="form-control" name="last_name" id="last_name" required>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="kin">Srodstvo</label>
            <select class="form-select" name="kin" id="kin" required>
                @foreach($kins as $kin)
                    <option value="{{ $kin->value }}">{{ $kin->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_of_birth">Rođenje</label>
            <input type="date" class="form-control" name="date_of_birth" id="date_of_birth">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="phone">Telefon</label>
            <input class="form-control" name="phone" id="phone">
        </div>
        <div class="col-md-5">
            <label class="form-label" for="note">Napomena</label>
            <input class="form-control" name="note" id="note">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_dependent" id="is_dependent" value="1">
                <label class="form-check-label" for="is_dependent">Uzdržavan</label>
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_emergency_contact" id="is_emergency_contact" value="1">
                <label class="form-check-label" for="is_emergency_contact">Hitni kontakt</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Dodaj</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Zaposlenik</th>
                    <th>Član</th>
                    <th>Srodstvo</th>
                    <th>Rođenje</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>
                            @if($member->person)
                                <a href="{{ route('organization.people.edit', [$organization->slug, $member->person, 'tab' => 'obitelj']) }}">{{ $member->person->fullName() }}</a>
                            @endif
                        </td>
                        <td>{{ $member->fullName() }}{{ $member->is_dependent ? ' · uzdržavan' : '' }}{{ $member->is_emergency_contact ? ' · hitni' : '' }}</td>
                        <td>{{ $member->kin->label() }}</td>
                        <td>{{ $member->date_of_birth?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.family.destroy', [$organization->slug, $member]) }}" onsubmit="return confirm('Ukloniti člana?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema unesenih članova obitelji.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
