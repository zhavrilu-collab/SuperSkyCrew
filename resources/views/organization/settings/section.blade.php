@if($tab === 'organizacija' && $section === 'osnovni-podaci')
    <form method="POST" action="{{ route('organization.settings.organization', $organization->slug) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="name">Naziv organizacije</label>
                <input class="form-control" name="name" id="name" value="{{ old('name', $organization->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Slug</label>
                <input class="form-control" value="{{ $organization->slug }}" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="oib">OIB</label>
                <input class="form-control" name="oib" id="oib" value="{{ old('oib', $organization->oib) }}" required>
                <div class="form-text">{{ ($organization->organization_type ?? \App\Enums\OrganizationType::Company)->oibHint() }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="organization_email">E-mail</label>
                <input type="email" class="form-control" name="organization_email" id="organization_email" value="{{ old('organization_email', $organization->email) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Telefon</label>
                <input class="form-control" name="phone" id="phone" value="{{ old('phone', $organization->phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="city">Grad</label>
                <input class="form-control" name="city" id="city" value="{{ old('city', $organization->city) }}">
            </div>
            <div class="col-md-8">
                <label class="form-label" for="street">Adresa sjedišta</label>
                <input class="form-control" name="street" id="street" value="{{ old('street', $organization->street) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="mbs">{{ ($organization->organization_type ?? \App\Enums\OrganizationType::Company)->registryNumberLabel() }}</label>
                <input class="form-control" name="mbs" id="mbs" value="{{ old('mbs', $organization->mbs) }}">
                <div class="form-text">{{ ($organization->organization_type ?? \App\Enums\OrganizationType::Company)->registryNumberHint() }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="nkd">NKD</label>
                <input class="form-control" name="nkd" id="nkd" value="{{ old('nkd', $organization->nkd) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="website">Web</label>
                <input class="form-control" name="website" id="website" value="{{ old('website', $organization->website) }}" placeholder="https://">
            </div>
            <div class="col-md-8">
                <label class="form-label" for="organization_type">Tip organizacije</label>
                <select class="form-select" name="organization_type" id="organization_type">
                    @foreach(\App\Enums\OrganizationType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('organization_type', $organization->organization_type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Spremi</button>
        </div>
    </form>
    @if($organization->logoUrl())
        <div class="mt-4 p-3 border rounded-3 bg-white d-inline-block">
            <div class="text-muted small mb-2">Logotip (upload u Izgledu)</div>
            <img src="{{ $organization->logoUrl() }}" alt="" style="max-height: 64px;">
        </div>
    @endif
@elseif($tab === 'organizacija' && $section === 'izgled')
    @include('organization.settings.partials.izgled')
@elseif($tab === 'organizacija' && $section === 'ustroj')
    @include('organization.structure.panel')
@elseif($tab === 'kadar' && $section === 'vrste-dokumenata')
    <p class="text-muted small mb-3">Korisnik definira vrste dokumenata dosjea. Sistemske vrste se ne brišu. Datoteke po osobi dodaju se na kartici.</p>
    <div class="table-responsive table-responsive-no-sticky mb-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>Šifra</th><th>Naziv</th><th>Klasa čuvanja</th><th>Istek</th><th></th></tr></thead>
            <tbody>
                @foreach($documentTypes as $type)
                    <tr>
                        <td><code>{{ $type->code }}</code></td>
                        <td>{{ $type->name }}{{ $type->is_system ? ' · sistemska' : '' }}</td>
                        <td>{{ $type->retention_class->label() }}</td>
                        <td>{{ $type->tracks_expiry ? 'da' : 'ne' }}</td>
                        <td class="text-end">
                            @unless($type->is_system)
                                <form method="POST" action="{{ route('organization.settings.document-types.destroy', [$organization->slug, $type]) }}" onsubmit="return confirm('Obrisati vrstu?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.settings.document-types.store', $organization->slug) }}">
        @csrf
        <h2 class="h6">Nova vrsta</h2>
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label" for="doc_code">Šifra</label><input class="form-control" name="code" id="doc_code" required maxlength="32" placeholder="viza"></div>
            <div class="col-md-4"><label class="form-label" for="doc_name">Naziv</label><input class="form-control" name="name" id="doc_name" required maxlength="120"></div>
            <div class="col-md-4">
                <label class="form-label" for="retention_class">Klasa čuvanja</label>
                <select class="form-select" name="retention_class" id="retention_class">
                    @foreach($retentionClasses as $class)
                        <option value="{{ $class->value }}">{{ $class->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end pb-1">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="tracks_expiry" id="tracks_expiry" value="1"><label class="form-check-label" for="tracks_expiry">Prati istek</label></div>
            </div>
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Dodaj vrstu</button>
        </div>
    </form>
@elseif($tab === 'kadar' && $section === 'predlosci')
    <p class="text-muted small mb-3">Ugrađeni Word predlošci (UOR, rješenje GO, uputnica) dolaze s organizacijom. Vlastite .docx datoteke ostavite polja kao jedan niz (npr. <code>@{{ime}}</code>). Ispunjeni akt sprema se u dosje osobe.</p>
    <table class="table table-sm mb-4">
        <thead><tr><th>Ugrađeni ispis</th><th>Vrsta</th><th>Gdje</th></tr></thead>
        <tbody>
            @foreach($documentTypes->whereNotNull('print_key') as $type)
                <tr>
                    <td>{{ $type->printLabel() ?: $type->name }}</td>
                    <td>{{ $type->name }}</td>
                    <td class="small text-muted">
                        @if($type->print_key === 'leave_decision')
                            HTML na odobrenom zahtjevu; Word u dosje nakon odobrenja
                        @else
                            Kartica osobe (HTML) i predložak u dosje
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h2 class="h6">Predlošci</h2>
    <div class="table-responsive table-responsive-no-sticky mb-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>Naziv</th><th>Vrsta</th><th>Datoteka</th><th></th></tr></thead>
            <tbody>
                @forelse($documentTemplates as $template)
                    <tr>
                        <td>
                            {{ $template->name }}
                            @if($template->is_system)
                                <span class="badge text-bg-light border">ugrađeni</span>
                            @endif
                        </td>
                        <td>{{ $template->documentType?->name ?: '—' }}</td>
                        <td>{{ $template->original_name }}</td>
                        <td class="text-end">
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('organization.settings.templates.download', [$organization->slug, $template]) }}">Preuzmi</a>
                            @unless($template->is_system)
                                <form method="POST" action="{{ route('organization.settings.templates.destroy', [$organization->slug, $template]) }}" class="d-inline" onsubmit="return confirm('Obrisati predložak?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Još nema predložaka.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.settings.templates.store', $organization->slug) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
        @csrf
        <div class="col-md-4"><label class="form-label" for="template_name">Naziv</label><input class="form-control" name="name" id="template_name" required maxlength="120"></div>
        <div class="col-md-3">
            <label class="form-label" for="template_type">Vrsta dokumenta</label>
            <select class="form-select" name="document_type_id" id="template_type">
                <option value="">—</option>
                @foreach($documentTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label" for="template_file">Datoteka</label><input class="form-control" type="file" name="file" id="template_file" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Dodaj</button></div>
    </form>
    <p class="form-text mb-1">Polja za Word merge:</p>
    <p class="small text-muted mb-0">{{ implode(' ', array_keys($mergeFields)) }}</p>
@elseif($tab === 'kadar' && $section === 'isteci')
    <form method="POST" action="{{ route('organization.settings.expiry', $organization->slug) }}">
        @csrf
        @method('PUT')
        <p class="form-label mb-2">Horizon upozorenja</p>
        <div class="d-flex flex-wrap gap-3 mb-2">
            @foreach($expiryWindows as $days)
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="expiry_warning_days" id="expiry_{{ $days }}" value="{{ $days }}" @checked((int) old('expiry_warning_days', $organization->expiry_warning_days ?? 30) === $days)>
                    <label class="form-check-label" for="expiry_{{ $days }}">{{ $days }} dana</label>
                </div>
            @endforeach
        </div>
        <p class="form-text">Signal na pregledu HR-a za UOR, dozvole, preglede, certifikate i dokumente dosjea. Unutar horizonta prikazuju se pragovi 5 / 10 / 20 / 30.</p>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Spremi</button>
        </div>
    </form>
@elseif($tab === 'kadar' && $section === 'zadrzavanje')
    <p class="text-muted small mb-3">Klase dosjea prema čl. 8.–9. Motor predlaže brisanje nakon isteka roka; datoteka se briše tek kad HR potvrdi. Zapis ostaje kao trag.</p>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0">
            <thead><tr><th>Klasa</th><th>Rok</th></tr></thead>
            <tbody>
                @foreach($retentionClasses as $class)
                    <tr>
                        <td>{{ $class->label() }}</td>
                        <td>{{ $class->period() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="forma-sekcija mt-0">
        <h2>Predloženo za brisanje</h2>
        <p>Pregled se osvježava pri otvaranju ovog ekrana. Cron naredba <code>hr:retention-propose</code> radi isto u pozadini.</p>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Dokument</th>
                    <th>Čuvati do</th>
                    <th>Predloženo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($proposedDocuments as $document)
                    <tr>
                        <td>
                            <a href="{{ route('organization.people.edit', [$organization->slug, $document->person, 'tab' => 'dokumenti']) }}">{{ $document->person?->fullName() ?: '—' }}</a>
                        </td>
                        <td>{{ $document->label() }}</td>
                        <td>{{ $document->retain_until?->format('d.m.Y.') ?: '—' }}</td>
                        <td>{{ $document->retention_proposed_at?->format('d.m.Y.') ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.settings.retention.confirm', [$organization->slug, $document]) }}" onsubmit="return confirm('Ukloniti datoteku? Zapis ostaje.');">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm" type="submit">Potvrdi brisanje</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema dokumenata kojima je istekao rok čuvanja.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($disposedDocuments->isNotEmpty())
        <div class="forma-sekcija">
            <h2>Nedavno uklonjene datoteke</h2>
            <p>Samo trag. Sadržaj datoteke je obrisan.</p>
        </div>
        <div class="table-responsive table-responsive-no-sticky">
            <table class="table table-sm mb-0">
                <thead><tr><th>Osoba</th><th>Dokument</th><th>Uklonjeno</th></tr></thead>
                <tbody>
                    @foreach($disposedDocuments as $document)
                        <tr>
                            <td>{{ $document->person?->fullName() ?: '—' }}</td>
                            <td>{{ $document->label() }}</td>
                            <td>{{ $document->disposed_at?->format('d.m.Y. H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@elseif($tab === 'kadar' && $section === 'volonteri')
    <form method="POST" action="{{ route('organization.settings.volunteer', $organization->slug) }}">
        @csrf
        @method('PUT')
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="volunteer_module" id="volunteer_module" value="1" @checked(old('volunteer_module', $organization->volunteer_module || $organization->isNonprofit()))>
            <label class="form-check-label" for="volunteer_module">Uključi status volontera u kadru</label>
        </div>
        <p class="form-text">Kad je uključeno, status Volonter pojavljuje se na kartici. Volonter nije u matičnoj knjizi ni na šihterici. Grant sati nisu v1. Volonter je odvojen od članova udruga-saasa.</p>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Spremi</button>
        </div>
    </form>
@elseif($tab === 'vrijeme' && $section === 'sifarnik')
    <p class="text-muted small mb-3">Kratica mora imati pisano značenje (čl. 18. st. 2.). Sistemske šifre čl. 13. se ne brišu; možete dodati vlastite.</p>
    <div class="table-responsive table-responsive-no-sticky mb-3">
        <table class="table table-sm mb-0">
            <thead><tr><th>Šifra</th><th>Naziv i značenje</th><th>Kategorija</th><th></th></tr></thead>
            <tbody>
                @foreach($absenceCodes as $code)
                    <tr>
                        <td><span class="badge {{ $code->badgeClass() }}">{{ $code->code }}</span></td>
                        <td>
                            <div>{{ $code->name }}{{ $code->paid ? '' : ' · neplaćeno' }}{{ $code->consumes_annual_leave ? ' · troši GO' : '' }}</div>
                            <div class="small text-muted">{{ $code->meaning ?: '—' }}</div>
                        </td>
                        <td>{{ $code->categoryLabel() }}</td>
                        <td class="text-end">
                            @unless($code->is_system)
                                <form method="POST" action="{{ route('organization.settings.codes.destroy', [$organization->slug, $code]) }}" onsubmit="return confirm('Obrisati šifru?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.settings.codes.store', $organization->slug) }}">
        @csrf
        <h2 class="h6">Nova šifra</h2>
        <div class="row g-2">
            <div class="col-md-2"><label class="form-label" for="code">Šifra</label><input class="form-control" name="code" id="code" required maxlength="16"></div>
            <div class="col-md-4"><label class="form-label" for="name">Naziv</label><input class="form-control" name="name" id="name" required></div>
            <div class="col-md-3">
                <label class="form-label" for="category">Kategorija</label>
                <select class="form-select" name="category" id="category">
                    <option value="presence">Prisutnost</option>
                    <option value="leave">Odsutnost</option>
                    <option value="sick">Bolovanje</option>
                    <option value="holiday">Blagdan</option>
                    <option value="other">Ostalo</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-3 pb-1">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="paid" id="paid" value="1" checked><label class="form-check-label" for="paid">Plaćeno</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="consumes_annual_leave" id="consumes_annual_leave" value="1"><label class="form-check-label" for="consumes_annual_leave">Troši GO</label></div>
            </div>
            <div class="col-12"><label class="form-label" for="meaning">Pisano značenje</label><input class="form-control" name="meaning" id="meaning" required maxlength="255" placeholder="Što kratica znači na ispisu za inspekciju"></div>
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Dodaj šifru</button>
        </div>
    </form>
@elseif($tab === 'vrijeme' && $section === 'smjene')
    <p class="text-muted small mb-3">Tjedni plan ostaje u modulu Vrijeme. Ovdje se vode šifrarnik smjena.</p>
    <div class="table-responsive table-responsive-no-sticky mb-3">
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
                        <td>{{ $shift->code ? $shift->code.' · ' : '' }}{{ $shift->name }}{{ $shift->is_shift ? ' · smjena' : '' }}{{ $shift->is_night ? ' · noć' : '' }}</td>
                        <td>{{ $shift->clockRange() }}</td>
                        <td>{{ $shift->break_minutes }} min</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.schedule.shifts.destroy', [$organization->slug, $shift]) }}" onsubmit="return confirm('Obrisati smjenu?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                            </form>
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
    <form method="POST" action="{{ route('organization.schedule.shifts.store', $organization->slug) }}">
        @csrf
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
                    <input class="form-check-input" type="checkbox" name="is_shift" id="is_shift" value="1">
                    <label class="form-check-label" for="is_shift">Smjenski rad (svi sati tog dana)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_night" id="is_night" value="1">
                    <label class="form-check-label" for="is_night">Noćna smjena</label>
                </div>
            </div>
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Spremi smjenu</button>
        </div>
    </form>
@elseif($tab === 'vrijeme' && $section === 'kalendari')
    <p class="text-muted small mb-3">Četiri razine: radnik, radno mjesto, odjel, organizacija. Specifičnija pobjeđuje.</p>
    <div class="table-responsive table-responsive-no-sticky mb-3">
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
                            <form method="POST" action="{{ route('organization.schedule.rules.destroy', [$organization->slug, $rule]) }}" onsubmit="return confirm('Obrisati pravilo?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                            </form>
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
    <form method="POST" action="{{ route('organization.schedule.rules.store', $organization->slug) }}">
        @csrf
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
                <input type="date" class="form-control" name="valid_from" id="valid_from" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="valid_to">Važi do</label>
                <input type="date" class="form-control" name="valid_to" id="valid_to">
            </div>
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Spremi pravilo</button>
        </div>
    </form>
@elseif($tab === 'vrijeme' && $section === 'lokacije')
    <p class="text-muted small mb-3">Pravila žive na lokaciji: geofence, obavezna fotografija (bez prepoznavanja lica, 30 dana), naknadni unos i veza PWA uređaja.</p>
    <div class="table-responsive table-responsive-no-sticky mb-3">
        <table class="table table-sm">
            <thead><tr><th>Lokacija</th><th>Pravila</th><th>Kiosk</th></tr></thead>
            <tbody>
                @forelse($locations as $location)
                    <tr>
                        <td>
                            {{ $location->name }}
                            @if($location->kioskUrl())
                                <div><a class="small" href="{{ $location->kioskUrl() }}" target="_blank" rel="noopener">Otvori kiosk</a></div>
                                <div><a class="small" href="{{ route('organization.settings.locations.kiosk-qr', [$organization->slug, $location]) }}">QR za tablet</a></div>
                            @endif
                            @if($location->entranceUrl())
                                <div><a class="small" href="{{ route('organization.settings.locations.entrance-qr', [$organization->slug, $location]) }}">Ulazni QR</a></div>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('organization.settings.locations.update', [$organization->slug, $location]) }}" class="row g-2 align-items-end">
                                @csrf
                                @method('PUT')
                                <div class="col-md-3">
                                    <label class="form-label small mb-0">Geofence</label>
                                    <select class="form-select form-select-sm" name="geofence_mode">
                                        @foreach($geofenceModes as $mode)
                                            <option value="{{ $mode->value }}" @selected($location->geofence_mode === $mode)>{{ $mode->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Radius m</label>
                                    <input type="number" class="form-control form-control-sm" name="radius_meters" value="{{ $location->radius_meters }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Grace min</label>
                                    <input type="number" min="0" max="60" class="form-control form-control-sm" name="punch_grace_minutes" value="{{ $location->punch_grace_minutes ?? 5 }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Zaokruži</label>
                                    <input type="number" min="0" max="30" class="form-control form-control-sm" name="punch_round_minutes" value="{{ $location->punch_round_minutes ?? 0 }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0">Uređaj</label>
                                    <select class="form-select form-select-sm" name="device_bind_mode">
                                        @foreach($deviceBindModes as $mode)
                                            <option value="{{ $mode->value }}" @selected($location->device_bind_mode === $mode)>{{ $mode->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex flex-wrap gap-3 pt-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="require_photo" value="1" id="foto-{{ $location->id }}" @checked($location->require_photo)>
                                        <label class="form-check-label" for="foto-{{ $location->id }}">Foto</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allow_offline" value="1" id="off-{{ $location->id }}" @checked($location->allow_offline)>
                                        <label class="form-check-label" for="off-{{ $location->id }}">Offline</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kiosk_enabled" value="1" id="kiosk-{{ $location->id }}" @checked($location->kiosk_enabled)>
                                        <label class="form-check-label" for="kiosk-{{ $location->id }}">Kiosk</label>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Spremi</button>
                                </div>
                                <div class="col-12">
                                    <span class="small text-muted me-2">Kanali (prazno = svi):</span>
                                    @foreach($clockChannels as $channel)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="allowed_channels[]" value="{{ $channel->value }}" id="ch-{{ $location->id }}-{{ $channel->value }}" @checked(is_array($location->allowed_channels) && in_array($channel->value, $location->allowed_channels, true))>
                                            <label class="form-check-label small" for="ch-{{ $location->id }}-{{ $channel->value }}">{{ $channel->label() }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </form>
                        </td>
                        <td>{{ $location->kiosk_enabled ? 'da' : 'ne' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">Još nema lokacija.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.settings.locations.store', $organization->slug) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3"><label class="form-label">Naziv</label><input class="form-control" name="name" required></div>
        <div class="col-md-2">
            <label class="form-label">Geofence</label>
            <select class="form-select" name="geofence_mode">
                @foreach($geofenceModes as $mode)
                    <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Uređaj</label>
            <select class="form-select" name="device_bind_mode">
                @foreach($deviceBindModes as $mode)
                    <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5 d-flex flex-wrap gap-3 pb-1">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="require_photo" value="1" id="foto_new">
                <label class="form-check-label" for="foto_new">Foto</label>
            </div>
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="allow_offline" value="1" id="off_new" checked>
                <label class="form-check-label" for="off_new">Offline</label>
            </div>
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="kiosk_enabled" value="1" id="kiosk_new">
                <label class="form-check-label" for="kiosk_new">Kiosk</label>
            </div>
            <button class="btn btn-primary mt-3" type="submit">Dodaj lokaciju</button>
        </div>
    </form>
@elseif($tab === 'vrijeme' && $section === 'go-politika')
    <p class="text-muted small mb-3">Fond = max(osnova, fond radnog mjesta) + dodatak za staž (najviši dosegnuti prag, ne zbroj) + dani po djetetu. Stari GO ide prije novog. Ručna kartica se ne dira pri preračunu.</p>
    <form method="POST" action="{{ route('organization.settings.leave', $organization->slug) }}" class="row g-3 mb-4">
        @csrf
        @method('PUT')
        <div class="col-md-4">
            <label class="form-label" for="annual_leave_base_days">Osnovni fond GO (dana)</label>
            <input type="number" min="0" max="50" class="form-control" name="annual_leave_base_days" id="annual_leave_base_days" value="{{ old('annual_leave_base_days', $organization->annual_leave_base_days ?? 20) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="annual_leave_days_per_child">Dana po djetetu</label>
            <input type="number" min="0" max="10" class="form-control" name="annual_leave_days_per_child" id="annual_leave_days_per_child" value="{{ old('annual_leave_days_per_child', $organization->annual_leave_days_per_child ?? 0) }}" required>
            <p class="form-text">Broj djece je na kartici (Plaće i prava).</p>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="forma-podnozje mt-0 pt-0 border-0 w-100">
                <button class="btn btn-primary" type="submit">Spremi politiku</button>
            </div>
        </div>
    </form>

    <div class="forma-sekcija mt-0">
        <h2>Pragovi staža</h2>
        <p>Ukupan staž = mjeseci kod ovog poslodavca + staž prije s kartice, na današnji dan (odnosno 31. 12. za prošle godine).</p>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-3">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Od (godina staža)</th>
                    <th>Dodatni dani</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveTenureRules as $rule)
                    <tr>
                        <td>{{ $rule->min_years }}</td>
                        <td>+{{ $rule->extra_days }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('organization.settings.leave.rules.destroy', [$organization->slug, $rule]) }}" onsubmit="return confirm('Ukloniti prag?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted">Nema pragova — fond je samo osnova, radno mjesto i djeca.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('organization.settings.leave.rules.store', $organization->slug) }}" class="row g-2 align-items-end mb-4">
        @csrf
        <div class="col-md-3">
            <label class="form-label" for="min_years">Od godina staža</label>
            <input type="number" min="0" max="50" class="form-control" name="min_years" id="min_years" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="extra_days">Dodatni dani</label>
            <input type="number" min="1" max="20" class="form-control" name="extra_days" id="extra_days" required>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" type="submit">Dodaj prag</button>
        </div>
    </form>

    <div class="forma-sekcija mt-0 d-flex justify-content-between align-items-baseline flex-wrap gap-2">
        <h2 class="mb-0">Stanje GO {{ now()->year }}.</h2>
        <form method="POST" action="{{ route('organization.settings.leave.recalculate', $organization->slug) }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm" type="submit">Preračunaj fondove</button>
        </form>
    </div>
    <div class="table-responsive table-responsive-no-sticky mb-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Osoba</th>
                    <th>Staž</th>
                    <th>Fond</th>
                    <th>Staro</th>
                    <th>Iskorišteno</th>
                    <th>Preostalo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveBalances as $row)
                    <tr>
                        <td>
                            <a href="{{ route('organization.people.edit', [$organization->slug, $row['person'], 'tab' => 'zaposlenje']) }}">{{ $row['person']->fullName() }}</a>
                            <div class="small text-muted">{{ $row['leave']['manual'] ? 'ručno' : 'politika' }}</div>
                        </td>
                        <td>{{ $row['leave']['tenure_years'] }} g.</td>
                        <td>{{ $row['leave']['entitled'] }}</td>
                        <td>{{ $row['leave']['carried'] }}</td>
                        <td>{{ $row['leave']['used'] }}</td>
                        <td>{{ $row['leave']['remaining'] }} ({{ $row['leave']['remaining_old'] }}/{{ $row['leave']['remaining_new'] }})</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-muted">Nema radnika na čl. 3. za ovu godinu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($tab === 'vrijeme' && $section === 'zakljucavanje')
    <p class="text-muted small mb-3">
        Unos sloga je zakonski do 7. dana od dana na koji se podatak odnosi (NN 55/2024).
        Job <code>hr:close-time</code> svake noći zatvara jučerašnje otvorene prijave (iznimka zaboravljene odjave)
        i, od odabranog dana u mjesecu, zaključava <strong>prethodni</strong> mjesec. Ručno zaključavanje na šihterici i dalje radi.
    </p>
    <form method="POST" action="{{ route('organization.settings.period-lock', $organization->slug) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="period_lock_day">Dan automatskog zaključavanja</label>
                <select class="form-select" name="period_lock_day" id="period_lock_day">
                    <option value="0" @selected((int) old('period_lock_day', $organization->period_lock_day ?? 8) === 0)>Isključeno (samo ručno)</option>
                    @for($day = 1; $day <= 28; $day++)
                        <option value="{{ $day }}" @selected((int) old('period_lock_day', $organization->period_lock_day ?? 8) === $day)>
                            {{ $day }}. u mjesecu{{ $day === 8 ? ' (preporučeno)' : '' }}
                        </option>
                    @endfor
                </select>
                <div class="form-text">8. je dan nakon zakonskog roka za zadnji dan prethodnog mjeseca.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Inspekcijski ispis</label>
                <div class="form-check mt-2">
                    <input type="hidden" name="show_clock_bounds" value="0">
                    <input class="form-check-input" type="checkbox" name="show_clock_bounds" value="1" id="show_clock_bounds" @checked(old('show_clock_bounds', $organization->show_clock_bounds ?? true))>
                    <label class="form-check-label" for="show_clock_bounds">Prikaži početak i završetak rada (čl. 13. t. 3.–4.)</label>
                </div>
                <div class="form-text">Punch se uvijek bilježi. Na zakonskom ispisu sati početka/kraja idu samo ako je to ugovoreno.</div>
            </div>
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Spremi</button>
        </div>
    </form>
@elseif($tab === 'vrijeme' && $section === 'obavijesti')
    <p class="text-muted small mb-3">Dnevni job <code>hr:reminders</code> šalje e-mail u temi organizacije (najprije zatvori jučerašnje slogove). Isti zapis se ne ponavlja (jednom po primatelju i događaju). Zahtjevi i dalje idu odmah iz radnog slijeda. Zaključavanje mjeseca: Vrijeme → Zaključavanje (<code>hr:close-time</code>).</p>
    <div class="table-responsive table-responsive-no-sticky mb-4">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Podsjetnik</th>
                    <th>Kome</th>
                    <th>Kada</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Istek dokumenata</td>
                    <td>HR / vlasnik (sažetak) i radnik sa spojenim korisnikom</td>
                    <td>Dok je rok unutar praga iz Kadrovi → Isteci</td>
                </tr>
                <tr>
                    <td>Zaboravljena odjava</td>
                    <td>Radnik i voditelj</td>
                    <td>Jučer i prethodna 2 dana, dok iznimka nije riješena</td>
                </tr>
                <tr>
                    <td>Nekompletan slog</td>
                    <td>HR / vlasnik (sažetak) i radnik</td>
                    <td>5. i 7. dan nakon datuma rada (čl. 13.)</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="forma-sekcija mt-0">
        <h2>Danas poslano</h2>
        <p>Broj poslanih poruka danas: {{ $reminderTodayCount }}. Pregled isteka: <a class="text-tema" href="{{ route('organization.expiries.index', $organization->slug) }}">Isteci</a> · iznimke: <a class="text-tema" href="{{ route('organization.exceptions.index', $organization->slug) }}">Iznimke</a>.</p>
    </div>
@elseif($tab === 'odobrenja')
    <p class="text-muted small">Grafički designer nije v1. Tablica koraka određuje tko odobrava. Voditelj se preskače ako osoba nema voditelja. GO: do 3 dana voditelj, dulje HR.</p>
    @foreach($workflows as $workflow)
        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2 mb-2">
                <h2 class="h6 mb-0">{{ $workflow->name }}</h2>
                <span class="small text-muted">{{ $workflow->type->label() }} · {{ $workflow->is_active ? 'aktivan' : 'neaktivan' }}</span>
            </div>
            <div class="table-responsive table-responsive-no-sticky mb-3">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Red</th><th>Uloga</th><th>Uvjet</th><th></th></tr></thead>
                    <tbody>
                        @forelse($workflow->steps as $step)
                            <tr>
                                <td>{{ $step->position }}</td>
                                <td>{{ $step->roleLabel() }}</td>
                                <td>{{ $step->conditionLabel() }}</td>
                                <td class="text-end">
                                    @if($workflow->steps->count() > 1)
                                        <form method="POST" action="{{ route('organization.settings.workflow-steps.destroy', [$organization->slug, $workflow, $step]) }}" onsubmit="return confirm('Ukloniti korak?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Nema koraka.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('organization.settings.workflow-steps.store', [$organization->slug, $workflow]) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Uloga</label>
                    <select class="form-select" name="role">
                        @foreach($stepRoles as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Od (dana)</label><input type="number" min="0" max="90" class="form-control" name="min_days" placeholder="—"></div>
                <div class="col-md-2"><label class="form-label">Do (dana)</label><input type="number" min="0" max="90" class="form-control" name="max_days" placeholder="—"></div>
                <div class="col-md-3"><button class="btn btn-outline-primary" type="submit">Dodaj korak</button></div>
            </form>
        </div>
    @endforeach
    @if($workflows->isEmpty())
        <p class="text-muted mb-0">Nema definiranih slijedova.</p>
    @endif
@elseif($tab === 'pristup' && $section === 'korisnici')
    @include('organization.team-panel')
@elseif($tab === 'pristup' && $section === 'prava')
    <p class="text-muted small">Matrica je read-only u v1. Uloge se dodjeljuju na kartici Korisnici.</p>
    <table class="table table-sm">
        <thead><tr><th>Pravo</th><th>Uloge</th></tr></thead>
        <tbody>
            @foreach($permissions as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['roles'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@elseif($tab === 'podaci' && $section === 'izvoz')
    <div class="d-flex flex-column gap-2 align-items-start">
        <a class="btn btn-outline-primary" href="{{ route('organization.people.export', $organization->slug) }}">Izvoz kadra (CSV)</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.export', $organization->slug) }}">Izvoz šihterice (CSV)</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.fund', $organization->slug) }}">Mjesečni fond</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.payroll-hours', $organization->slug) }}">Sati za plaće</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.timesheet.inspection', $organization->slug) }}">Inspekcijski paket</a>
        <a class="btn btn-outline-primary" href="{{ route('organization.settings.index', ['slug' => $organization->slug, 'tab' => 'podaci', 'section' => 'trag']) }}">Revizijski trag</a>
    </div>
@elseif($tab === 'podaci' && $section === 'uvoz')
    <p class="text-muted small mb-3">Uvoz kadra iz CSV-a (točka-zarez). Osoba s postojećim OIB-om se ažurira; bez OIB-a se uvijek dodaje nova. Uvoz šihterice nije u ovom cutu.</p>
    <a class="btn btn-outline-primary btn-sm mb-3" href="{{ route('organization.settings.import.template', $organization->slug) }}">Preuzmi predložak CSV</a>
    <form method="POST" action="{{ route('organization.settings.import', $organization->slug) }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3 col-md-6">
            <label class="form-label" for="import_file">CSV datoteka</label>
            <input class="form-control" type="file" name="file" id="import_file" required accept=".csv,text/csv,text/plain">
        </div>
        <div class="forma-podnozje">
            <button class="btn btn-primary" type="submit">Uvezi kadar</button>
        </div>
    </form>
    @if(session('import_skipped'))
        <div class="table-responsive table-responsive-no-sticky mt-3">
            <table class="table table-sm mb-0">
                <thead><tr><th>Red</th><th>Razlog</th></tr></thead>
                <tbody>
                    @foreach(session('import_skipped') as $skip)
                        <tr>
                            <td>{{ $skip['row'] }}</td>
                            <td>{{ $skip['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@elseif($tab === 'podaci' && $section === 'trag')
    <p class="text-muted small mb-3">Zapis privilegiranih radnji (čl. 5. st. 6.): ručni punch, korekcija, zaključavanje, izvoz, predaja, brisanje dosjea, prijenos kandidata. Zapisi se ne brišu. ESS prijava/odjava ostaje na punchu.</p>
    <form method="GET" action="{{ route('organization.settings.index', $organization->slug) }}" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="tab" value="podaci">
        <input type="hidden" name="section" value="trag">
        <div class="col-md-2">
            <label class="form-label" for="od">Od</label>
            <input type="date" class="form-control" name="od" id="od" value="{{ $auditFrom->toDateString() }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="do">Do</label>
            <input type="date" class="form-control" name="do" id="do" value="{{ $auditTo->toDateString() }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="radnja">Radnja</label>
            <select class="form-select" name="radnja" id="radnja">
                <option value="">Sve</option>
                @foreach($auditActions as $action)
                    <option value="{{ $action->value }}" @selected($auditAction === $action->value)>{{ $action->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="osoba">Osoba</label>
            <select class="form-select" name="osoba" id="osoba">
                <option value="">Sve</option>
                @foreach($auditPeople as $auditPerson)
                    <option value="{{ $auditPerson->id }}" @selected((int) $auditPersonId === (int) $auditPerson->id)>{{ $auditPerson->fullName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary" type="submit">Prikaži</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Vrijeme</th>
                    <th>Tko</th>
                    <th>Radnja</th>
                    <th>Osoba</th>
                    <th>Sažetak</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditEvents as $event)
                    <tr>
                        <td>{{ $event->created_at?->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</td>
                        <td>{{ $event->actor?->name ?: 'sustav' }}</td>
                        <td>{{ $event->actionLabel() }}</td>
                        <td>{{ $event->person?->fullName() ?: '—' }}</td>
                        <td>{{ $event->summary }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Nema zapisa u odabranom rasponu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($tab === 'pretplata')
    @php
        $features = app(\App\Services\FeatureService::class);
        $limit = $features->employeeLimit($organization);
        $used = $features->countedHeadcount($organization);
    @endphp
    <dl class="row mb-0">
        <dt class="col-sm-3">Plan</dt>
        <dd class="col-sm-9">{{ \App\Support\OrganizationFeatures::planLabel($organization->plan) }}</dd>
        <dt class="col-sm-3">Status</dt>
        <dd class="col-sm-9">{{ $organization->status->label() }}</dd>
        <dt class="col-sm-3">Probni period</dt>
        <dd class="col-sm-9">
            @if($organization->onTrial())
                još {{ $organization->trialDaysRemaining() }} {{ $organization->trialDaysRemaining() === 1 ? 'dan' : 'dana' }}
                (do {{ $organization->trial_ends_at->timezone(config('app.timezone'))->format('d.m.Y.') }})
            @elseif($organization->trialExpired())
                istekao {{ $organization->trial_ends_at?->timezone(config('app.timezone'))->format('d.m.Y.') }}
            @else
                nije aktivan
            @endif
        </dd>
        <dt class="col-sm-3">Stripe</dt>
        <dd class="col-sm-9">{{ $organization->stripe_subscription_id ?: 'nije povezano' }}</dd>
        <dt class="col-sm-3">Limit osoba</dt>
        <dd class="col-sm-9">{{ $limit ? $used.' / '.$limit : $used.' (bez limita)' }}</dd>
    </dl>
    <p class="text-muted small mt-3">Značajke paketa (Core konzola):</p>
    <ul class="small mb-0">
        @foreach($features->resolved($organization) as $key => $on)
            <li>{{ \App\Support\OrganizationFeatures::label($key) }}: {{ $on ? 'uključeno' : 'isključeno' }}</li>
        @endforeach
    </ul>
    <p class="text-muted small mt-3 mb-0">Naplata, limiti i produljenje probnog perioda uređuju se u Core konzoli.</p>
@endif
