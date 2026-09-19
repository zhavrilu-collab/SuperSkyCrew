<div class="kartica-kontejner mb-3">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>CV</h2>
        <p>Životopis ostaje na kartici i nakon prijenosa u kadar. Škole i kompetencije su na kartici Kvalifikacije; dozvola i boravište na Osobno.</p>
    </div>
    @if($person->hasCv())
        <p class="mb-2">
            <a class="text-tema" href="{{ route('organization.people.cv.download', [$organization->slug, $person]) }}">{{ $person->cv_original_name }}</a>
            <span class="small text-muted">· {{ $person->cv_uploaded_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</span>
        </p>
        <form method="POST" action="{{ route('organization.people.cv.destroy', [$organization->slug, $person]) }}" class="mb-3" onsubmit="return confirm('Ukloniti CV?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni CV</button>
        </form>
    @else
        <p class="text-muted small">Još nema priloženog CV-a.</p>
    @endif
    <form method="POST" action="{{ route('organization.people.cv.store', [$organization->slug, $person]) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-6">
            <label class="form-label" for="cv">Datoteka (PDF, DOC, DOCX)</label>
            <input class="form-control" type="file" name="cv" id="cv" required accept=".pdf,.doc,.docx,application/pdf">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" type="submit">Spremi CV</button>
        </div>
    </form>
</div>

<div class="kartica-kontejner">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Bilješke razgovora</h2>
        <p>Nije puni ATS: nema natječaja ni bodovanja. Povijest ostaje vidljiva nakon zapošljavanja.</p>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Ishod</th>
                    <th>Razgovarao</th>
                    <th>Bilješka</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($person->interviewNotes as $note)
                    <tr>
                        <td>{{ $note->occurred_on?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $note->outcomeLabel() }}</td>
                        <td>{{ $note->interviewerLabel() }}</td>
                        <td class="small">{{ $note->body }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.people.notes.destroy', [$organization->slug, $person, $note]) }}" onsubmit="return confirm('Ukloniti bilješku?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Još nema bilješki razgovora.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.people.notes.store', [$organization->slug, $person]) }}" class="row g-3">
        @csrf
        <div class="col-md-3">
            <label class="form-label" for="occurred_on">Datum</label>
            <input type="date" class="form-control" name="occurred_on" id="occurred_on" value="{{ old('occurred_on', now()->toDateString()) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="outcome">Ishod</label>
            <select class="form-select" name="outcome" id="outcome" required>
                @foreach($interviewOutcomes as $outcome)
                    <option value="{{ $outcome->value }}" @selected(old('outcome', 'held') === $outcome->value)>{{ $outcome->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="interviewer_user_id">Razgovarao (korisnik)</label>
            <select class="form-select" name="interviewer_user_id" id="interviewer_user_id">
                <option value="">—</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected((string) old('interviewer_user_id', auth()->id()) === (string) $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="interviewer_name">Ili vanjski ime</label>
            <input class="form-control" name="interviewer_name" id="interviewer_name" value="{{ old('interviewer_name') }}" maxlength="120">
        </div>
        <div class="col-12">
            <label class="form-label" for="body">Bilješka</label>
            <textarea class="form-control" name="body" id="body" rows="4" required maxlength="5000">{{ old('body') }}</textarea>
        </div>
        <div class="col-12">
            <div class="forma-podnozje">
                <button class="btn btn-primary" type="submit">Dodaj bilješku</button>
            </div>
        </div>
    </form>
</div>
