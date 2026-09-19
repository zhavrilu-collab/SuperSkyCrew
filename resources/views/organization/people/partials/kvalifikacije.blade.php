<div class="kartica-kontejner">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Obrazovanje i certifikati (čl. 3. st. 1. t. 8.)</h2>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Vrsta</th>
                    <th>Naziv</th>
                    <th>Izdavatelj</th>
                    <th>Izdano</th>
                    <th>Istek</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($person->qualifications as $item)
                    <tr>
                        <td>{{ $item->kind->label() }}@if($item->required_for_job) <span class="badge text-bg-secondary">uvjet</span>@endif</td>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->issuer ?: '—' }}</td>
                        <td>{{ $item->issued_on?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $item->expires_at?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.qualifications.destroy', [$organization->slug, $person, $item]) }}" onsubmit="return confirm('Ukloniti ovu stavku?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Još nema unosa. To je točka 8. pisanog pregleda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.qualifications.store', [$organization->slug, $person]) }}" class="row g-3">
        @csrf
        <div class="col-md-3">
            <label class="form-label" for="kind">Vrsta</label>
            <select class="form-select" name="kind" id="kind" required>
                @foreach($qualificationKinds as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind') === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label" for="title">Naziv</label>
            <input class="form-control" name="title" id="title" value="{{ old('title') }}" required maxlength="255" placeholder="npr. Mag. oec. / Viljuškar">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="issuer">Izdavatelj</label>
            <input class="form-control" name="issuer" id="issuer" value="{{ old('issuer') }}" maxlength="255">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="issued_on">Izdano</label>
            <input type="date" class="form-control" name="issued_on" id="issued_on" value="{{ old('issued_on') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="expires_at">Istek</label>
            <input type="date" class="form-control" name="expires_at" id="expires_at" value="{{ old('expires_at') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="note">Napomena</label>
            <input class="form-control" name="note" id="note" value="{{ old('note') }}" maxlength="255">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="required_for_job" id="required_for_job" value="1" @checked(old('required_for_job'))>
                <label class="form-check-label" for="required_for_job">Uvjet za posao</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-outline-primary" type="submit">Dodaj kvalifikaciju</button>
        </div>
    </form>
</div>
