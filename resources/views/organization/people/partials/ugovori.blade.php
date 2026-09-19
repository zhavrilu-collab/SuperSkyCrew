<div class="kartica-kontejner">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Ugovori i aneksi</h2>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Vrsta</th>
                    <th>Broj</th>
                    <th>Trajanje</th>
                    <th>Probni</th>
                    <th>Sati/tj.</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($person->employmentContracts as $item)
                    <tr>
                        <td>{{ $item->kind->label() }}{{ $item->is_current ? ' · važeći' : '' }}{{ $item->contract_type ? ' · '.$item->contract_type->label() : '' }}</td>
                        <td>{{ $item->number ?: '—' }}</td>
                        <td>{{ $item->starts_at->format('d.m.Y.') }}{{ $item->ends_at ? ' – '.$item->ends_at->format('d.m.Y.') : '' }}</td>
                        <td>{{ $item->trial_ends_at?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $item->weekly_hours ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.contracts.destroy', [$organization->slug, $person, $item]) }}" onsubmit="return confirm('Ukloniti ovaj ugovor?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Još nema ugovora. Ispis UOR-a i istek određenog čitaju važeći slog.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.contracts.store', [$organization->slug, $person]) }}" class="row g-3">
        @csrf
        <div class="col-md-3">
            <label class="form-label" for="instrument_kind">Isprava</label>
            <select class="form-select" name="kind" id="instrument_kind" required>
                @foreach($instrumentKinds as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', 'uor') === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_contract_type">Vrsta</label>
            <select class="form-select" name="contract_type" id="instrument_contract_type">
                <option value="">—</option>
                @foreach($contracts as $contract)
                    <option value="{{ $contract->value }}" @selected(old('contract_type', $person->contract_type?->value) === $contract->value)>{{ $contract->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_number">Broj</label>
            <input class="form-control" name="number" id="instrument_number" value="{{ old('number') }}" maxlength="64" placeholder="auto ako prazno">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_weekly_hours">Sati tjedno</label>
            <input type="number" min="1" max="60" class="form-control" name="weekly_hours" id="instrument_weekly_hours" value="{{ old('weekly_hours', 40) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_signed_at">Potpis</label>
            <input type="date" class="form-control" name="signed_at" id="instrument_signed_at" value="{{ old('signed_at', $person->started_at?->toDateString() ?? now()->toDateString()) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_starts_at">Početak</label>
            <input type="date" class="form-control" name="starts_at" id="instrument_starts_at" value="{{ old('starts_at', $person->started_at?->toDateString() ?? now()->toDateString()) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_ends_at">Istek (određeno)</label>
            <input type="date" class="form-control" name="ends_at" id="instrument_ends_at" value="{{ old('ends_at') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="instrument_trial_ends_at">Probni rad do</label>
            <input type="date" class="form-control" name="trial_ends_at" id="instrument_trial_ends_at" value="{{ old('trial_ends_at') }}">
        </div>
        <div class="col-md-9">
            <label class="form-label" for="instrument_note">Napomena</label>
            <input class="form-control" name="note" id="instrument_note" value="{{ old('note') }}" maxlength="255">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_current" id="instrument_is_current" value="1" @checked(old('is_current', '1'))>
                <label class="form-check-label" for="instrument_is_current">Važeći</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-outline-primary" type="submit">Dodaj ugovor</button>
        </div>
    </form>
</div>
