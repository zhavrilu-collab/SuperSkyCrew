<div class="kartica-kontejner">
    <div class="forma-sekcija mt-0 pt-0 border-0">
        <h2>Dokumenti dosjea</h2>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Vrsta</th>
                    <th>Naziv</th>
                    <th>Izdano</th>
                    <th>Istek</th>
                    <th>Datoteka</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($person->documents as $item)
                    <tr class="{{ $item->isDisposed() ? 'text-muted' : '' }}">
                        <td>{{ $item->documentType?->name ?: '—' }}</td>
                        <td>{{ $item->title ?: '—' }}</td>
                        <td>{{ $item->issued_on?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $item->expires_on?->format('d.m.Y.') ?: '—' }}</td>
                        <td>
                            @if($item->isDisposed())
                                uklonjeno {{ $item->disposed_at?->format('d.m.Y.') }}
                            @elseif($item->hasFile())
                                <a href="{{ route('organization.documents.download', [$organization->slug, $person, $item]) }}">{{ $item->original_name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            @unless($item->isDisposed())
                                <form method="POST" action="{{ route('organization.documents.destroy', [$organization->slug, $person, $item]) }}" onsubmit="return confirm('Ukloniti ovaj dokument?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Još nema dokumenata. Vrste se uređuju u Postavkama → Kadrovi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.documents.store', [$organization->slug, $person]) }}" class="row g-3" enctype="multipart/form-data">
        @csrf
        <div class="col-md-3">
            <label class="form-label" for="document_type_id">Vrsta</label>
            <select class="form-select" name="document_type_id" id="document_type_id" required>
                @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('document_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="document_title">Naziv / broj</label>
            <input class="form-control" name="title" id="document_title" value="{{ old('title') }}" maxlength="255" placeholder="opcionalno">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="document_issued_on">Izdano</label>
            <input type="date" class="form-control" name="issued_on" id="document_issued_on" value="{{ old('issued_on') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="document_expires_on">Istek</label>
            <input type="date" class="form-control" name="expires_on" id="document_expires_on" value="{{ old('expires_on') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="document_note">Napomena</label>
            <input class="form-control" name="note" id="document_note" value="{{ old('note') }}" maxlength="255">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="document_file">Datoteka</label>
            <input class="form-control" type="file" name="file" id="document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
        </div>
        <div class="col-12">
            <div class="forma-podnozje">
                <button class="btn btn-primary" type="submit">Dodaj dokument</button>
            </div>
        </div>
    </form>
    @if($documentTemplates->isNotEmpty())
        <h2 class="h6 mt-4">Ispuni predložak</h2>
        <p class="small text-muted">Preuzimanje ne dira dosje. Spremi u dosje upisuje ispunjeni akt kao dokument osobe.</p>
        <div class="table-responsive table-responsive-no-sticky mb-0">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Predložak</th>
                        <th>Datoteka</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documentTemplates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td class="small text-muted">{{ $template->original_name }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.documents.fill', [$organization->slug, $person, $template]) }}">Preuzmi</a>
                                <form method="POST" action="{{ route('organization.documents.fill-store', [$organization->slug, $person, $template]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-primary btn-sm" type="submit">Spremi u dosje</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Nema predložaka.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
