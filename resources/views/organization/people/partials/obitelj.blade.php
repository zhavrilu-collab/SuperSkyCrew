<div class="kartica-kontejner">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Članovi obitelji</h2>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Ime</th>
                    <th>Srodstvo</th>
                    <th>Rođenje</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($person->familyMembers as $member)
                    <tr>
                        <td>{{ $member->fullName() }}{{ $member->is_dependent ? ' · uzdržavan' : '' }}{{ $member->is_emergency_contact ? ' · hitni' : '' }}</td>
                        <td>{{ $member->kin->label() }}</td>
                        <td>{{ $member->date_of_birth?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.family.destroy', [$organization->slug, $member]) }}" onsubmit="return confirm('Ukloniti člana?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="from" value="person">
                                <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Nema unesenih članova.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.family.store', $organization->slug) }}" class="row g-3">
        @csrf
        <input type="hidden" name="person_id" value="{{ $person->id }}">
        <div class="col-md-3">
            <label class="form-label" for="fam_first">Ime</label>
            <input class="form-control" name="first_name" id="fam_first" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fam_last">Prezime</label>
            <input class="form-control" name="last_name" id="fam_last" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fam_kin">Srodstvo</label>
            <select class="form-select" name="kin" id="fam_kin" required>
                @foreach($kins as $kin)
                    <option value="{{ $kin->value }}" @selected($kin === \App\Enums\FamilyKin::Child)>{{ $kin->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="fam_dob">Rođenje</label>
            <input type="date" class="form-control" name="date_of_birth" id="fam_dob">
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="is_dependent" id="fam_dep" value="1">
                <label class="form-check-label" for="fam_dep">Uzdržavan</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="is_emergency_contact" id="fam_em" value="1">
                <label class="form-check-label" for="fam_em">Hitni kontakt</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-outline-primary" type="submit">Dodaj člana</button>
        </div>
    </form>
</div>
