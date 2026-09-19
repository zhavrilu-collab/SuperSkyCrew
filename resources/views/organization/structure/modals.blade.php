<div class="modal fade forma-modal" id="modal-odjel" tabindex="-1" aria-labelledby="modal-odjel-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.departments.store', $organization->slug) }}" class="modal-content" id="form-odjel">
            @csrf
            <input type="hidden" name="_method" value="POST" id="odjel-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-odjel-naslov">Novi odjel</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="dept_name">Naziv</label>
                        <input class="form-control" name="name" id="dept_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_code">Šifra</label>
                        <input class="form-control" name="code" id="dept_code">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dept_parent">Nadređeni odjel</label>
                        <select class="form-select" name="parent_id" id="dept_parent">
                            <option value="">— (vrh funkcijskog ustroja)</option>
                            @foreach($departments as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 {{ $enterpriseUnits->count() < 2 ? 'd-none' : '' }}">
                        <label class="form-label" for="dept_unit">Poslovna jedinica</label>
                        <select class="form-select" name="enterprise_unit_id" id="dept_unit">
                            @foreach($enterpriseUnits as $unit)
                                <option value="{{ $unit->id }}" @selected($selectedUnit?->id === $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dept_manager">Voditelj odjela</label>
                        <select class="form-select" name="manager_user_id" id="dept_manager">
                            <option value="">—</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="dept_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dept_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="dept_to">
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-mjesto" tabindex="-1" aria-labelledby="modal-mjesto-naslov">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('organization.structure.positions.store', $organization->slug) }}" class="modal-content" id="form-mjesto">
            @csrf
            <input type="hidden" name="_method" value="POST" id="mjesto-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-mjesto-naslov">Novo radno mjesto</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="forma-sekcija mt-0 pt-0 border-0">
                            <h2>Radno mjesto</h2>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="pos_name">Naziv</label>
                                <input class="form-control" name="name" id="pos_name" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="pos_department">Odjel</label>
                                <select class="form-select" name="department_id" id="pos_department">
                                    <option value="">—</option>
                                    @foreach($departments as $option)
                                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_rad1g">RAD1G</label>
                                <input class="form-control" name="rad1g" id="pos_rad1g">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_go">Fond GO</label>
                                <input type="number" min="0" max="50" class="form-control" name="annual_leave_days" id="pos_go">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_from">Važi od</label>
                                <input type="date" class="form-control" name="valid_from" id="pos_from" value="{{ $on->toDateString() }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pos_to">Važi do</label>
                                <input type="date" class="form-control" name="valid_to" id="pos_to">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="pos_description">Opis</label>
                                <textarea class="form-control" name="description" id="pos_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="forma-sekcija mt-0 pt-0 border-0">
                            <h2>Osobe na ovom mjestu</h2>
                            <p>Ugovor i datumi ostaju na kartici osobe.</p>
                        </div>
                        <pre class="small mb-0 bg-light rounded p-2" id="pos_people" style="white-space: pre-wrap; min-height: 8rem;">—</pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-mt" tabindex="-1" aria-labelledby="modal-mt-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.cost-centers.store', $organization->slug) }}" class="modal-content" id="form-mt">
            @csrf
            <input type="hidden" name="_method" value="POST" id="mt-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-mt-naslov">Novo mjesto troška</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="mt_code">Šifra</label>
                        <input class="form-control" name="code" id="mt_code" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="mt_name">Naziv</label>
                        <input class="form-control" name="name" id="mt_name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="mt_legal">Pravna osoba</label>
                        <select class="form-select" name="legal_entity_id" id="mt_legal">
                            <option value="">—</option>
                            @foreach($legalEntities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="mt_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="mt_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="mt_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="mt_to">
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-pravna" tabindex="-1" aria-labelledby="modal-pravna-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.legal-entities.store', $organization->slug) }}" class="modal-content" id="form-pravna">
            @csrf
            <input type="hidden" name="_method" value="POST" id="pravna-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-pravna-naslov">Nova pravna osoba</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="le_name">Naziv</label>
                        <input class="form-control" name="name" id="le_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="le_code">Šifra</label>
                        <input class="form-control" name="code" id="le_code">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="le_oib">OIB</label>
                        <input class="form-control" name="oib" id="le_oib" maxlength="11">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="le_parent">Nadređena osoba</label>
                        <select class="form-select" name="parent_id" id="le_parent">
                            <option value="">—</option>
                            @foreach($legalEntities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="le_street">Ulica</label>
                        <input class="form-control" name="street" id="le_street">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="le_city">Grad</label>
                        <input class="form-control" name="city" id="le_city">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="le_country">Država</label>
                        <input class="form-control" name="country" id="le_country" value="Hrvatska">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="le_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="le_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="le_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="le_to">
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-poslovnica" tabindex="-1" aria-labelledby="modal-poslovnica-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.work-centers.store', $organization->slug) }}" class="modal-content" id="form-poslovnica">
            @csrf
            <input type="hidden" name="_method" value="POST" id="poslovnica-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-poslovnica-naslov">Nova poslovnica</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="wc_name">Naziv</label>
                        <input class="form-control" name="name" id="wc_name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="wc_code">Šifra</label>
                        <input class="form-control" name="code" id="wc_code">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="wc_legal">Pravna osoba</label>
                        <select class="form-select" name="legal_entity_id" id="wc_legal">
                            <option value="">—</option>
                            @foreach($legalEntities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="wc_location">Lokacija prijave</label>
                        <select class="form-select" name="location_id" id="wc_location">
                            <option value="">— (nije kiosk)</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="wc_street">Ulica</label>
                        <input class="form-control" name="street" id="wc_street">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="wc_city">Grad</label>
                        <input class="form-control" name="city" id="wc_city">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="wc_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="wc_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="wc_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="wc_to">
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade forma-modal" id="modal-jedinica" tabindex="-1" aria-labelledby="modal-jedinica-naslov">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('organization.structure.enterprise-units.store', $organization->slug) }}" class="modal-content" id="form-jedinica">
            @csrf
            <input type="hidden" name="_method" value="POST" id="jedinica-method">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-jedinica-naslov">Nova poslovna jedinica</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="eu_name">Naziv</label>
                        <input class="form-control" name="name" id="eu_name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="eu_parent">Nadređena jedinica</label>
                        <select class="form-select" name="parent_id" id="eu_parent">
                            <option value="">— (vrh)</option>
                            @foreach($enterpriseUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="eu_legal">Pravna osoba</label>
                        <select class="form-select" name="legal_entity_id" id="eu_legal">
                            <option value="">—</option>
                            @foreach($legalEntities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="eu_work">Poslovnica</label>
                        <select class="form-select" name="work_center_id" id="eu_work">
                            <option value="">—</option>
                            @foreach($workCenters as $center)
                                <option value="{{ $center->id }}">{{ $center->summary() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="eu_from">Važi od</label>
                        <input type="date" class="form-control" name="valid_from" id="eu_from" value="{{ $on->toDateString() }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="eu_to">Važi do</label>
                        <input type="date" class="form-control" name="valid_to" id="eu_to">
                    </div>
                </div>
            </div>
            <div class="modal-footer forma-podnozje mt-0 pt-3">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var today = @json($on->toDateString());
    var storeOdjel = @json(route('organization.structure.departments.store', $organization->slug));
    var storeMjesto = @json(route('organization.structure.positions.store', $organization->slug));
    var storeMt = @json(route('organization.structure.cost-centers.store', $organization->slug));
    var storePravna = @json(route('organization.structure.legal-entities.store', $organization->slug));
    var storePoslovnica = @json(route('organization.structure.work-centers.store', $organization->slug));
    var storeJedinica = @json(route('organization.structure.enterprise-units.store', $organization->slug));

    function resetOdjel(btn) {
        var form = document.getElementById('form-odjel');
        form.action = storeOdjel;
        document.getElementById('odjel-method').value = 'POST';
        document.getElementById('modal-odjel-naslov').textContent = 'Novi odjel';
        document.getElementById('dept_name').value = '';
        document.getElementById('dept_code').value = '';
        document.getElementById('dept_parent').value = (btn && btn.getAttribute('data-parent')) || '';
        document.getElementById('dept_unit').value = (btn && btn.getAttribute('data-unit')) || document.getElementById('dept_unit').value;
        document.getElementById('dept_manager').value = '';
        document.getElementById('dept_from').value = today;
        document.getElementById('dept_to').value = '';
    }
    function resetMjesto(btn) {
        var form = document.getElementById('form-mjesto');
        form.action = storeMjesto;
        document.getElementById('mjesto-method').value = 'POST';
        document.getElementById('modal-mjesto-naslov').textContent = 'Novo radno mjesto';
        document.getElementById('pos_name').value = '';
        document.getElementById('pos_department').value = (btn && btn.getAttribute('data-department')) || '';
        document.getElementById('pos_rad1g').value = '';
        document.getElementById('pos_go').value = '';
        document.getElementById('pos_description').value = '';
        document.getElementById('pos_from').value = today;
        document.getElementById('pos_to').value = '';
        document.getElementById('pos_people').textContent = '—';
    }
    function resetMt() {
        document.getElementById('form-mt').action = storeMt;
        document.getElementById('mt-method').value = 'POST';
        document.getElementById('modal-mt-naslov').textContent = 'Novo mjesto troška';
        document.getElementById('mt_code').value = '';
        document.getElementById('mt_name').value = '';
        document.getElementById('mt_legal').value = '';
        document.getElementById('mt_from').value = today;
        document.getElementById('mt_to').value = '';
    }
    function resetPravna() {
        document.getElementById('form-pravna').action = storePravna;
        document.getElementById('pravna-method').value = 'POST';
        document.getElementById('modal-pravna-naslov').textContent = 'Nova pravna osoba';
        document.getElementById('le_name').value = '';
        document.getElementById('le_code').value = '';
        document.getElementById('le_oib').value = '';
        document.getElementById('le_parent').value = '';
        document.getElementById('le_street').value = '';
        document.getElementById('le_city').value = '';
        document.getElementById('le_country').value = 'Hrvatska';
        document.getElementById('le_from').value = today;
        document.getElementById('le_to').value = '';
    }
    function resetPoslovnica() {
        document.getElementById('form-poslovnica').action = storePoslovnica;
        document.getElementById('poslovnica-method').value = 'POST';
        document.getElementById('modal-poslovnica-naslov').textContent = 'Nova poslovnica';
        document.getElementById('wc_name').value = '';
        document.getElementById('wc_code').value = '';
        document.getElementById('wc_legal').value = '';
        document.getElementById('wc_location').value = '';
        document.getElementById('wc_street').value = '';
        document.getElementById('wc_city').value = '';
        document.getElementById('wc_from').value = today;
        document.getElementById('wc_to').value = '';
    }
    function resetJedinica(btn) {
        document.getElementById('form-jedinica').action = storeJedinica;
        document.getElementById('jedinica-method').value = 'POST';
        document.getElementById('modal-jedinica-naslov').textContent = 'Nova poslovna jedinica';
        document.getElementById('eu_name').value = '';
        document.getElementById('eu_parent').value = (btn && btn.getAttribute('data-parent')) || '';
        document.getElementById('eu_legal').value = '';
        document.getElementById('eu_work').value = '';
        document.getElementById('eu_from').value = today;
        document.getElementById('eu_to').value = '';
    }

    document.getElementById('modal-odjel').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetOdjel(btn); return; }
        document.getElementById('form-odjel').action = btn.getAttribute('data-action');
        document.getElementById('odjel-method').value = 'PUT';
        document.getElementById('modal-odjel-naslov').textContent = 'Uredi odjel';
        document.getElementById('dept_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('dept_code').value = btn.getAttribute('data-code') || '';
        document.getElementById('dept_parent').value = btn.getAttribute('data-parent') || '';
        document.getElementById('dept_unit').value = btn.getAttribute('data-unit') || '';
        document.getElementById('dept_manager').value = btn.getAttribute('data-manager') || '';
        document.getElementById('dept_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('dept_to').value = btn.getAttribute('data-to') || '';
    });
    document.getElementById('modal-mjesto').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetMjesto(btn); return; }
        document.getElementById('form-mjesto').action = btn.getAttribute('data-action');
        document.getElementById('mjesto-method').value = 'PUT';
        document.getElementById('modal-mjesto-naslov').textContent = 'Uredi radno mjesto';
        document.getElementById('pos_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('pos_department').value = btn.getAttribute('data-department') || '';
        document.getElementById('pos_rad1g').value = btn.getAttribute('data-rad1g') || '';
        document.getElementById('pos_go').value = btn.getAttribute('data-go') || '';
        document.getElementById('pos_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('pos_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('pos_to').value = btn.getAttribute('data-to') || '';
        document.getElementById('pos_people').textContent = btn.getAttribute('data-people') || '—';
    });
    document.getElementById('modal-mt').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetMt(); return; }
        document.getElementById('form-mt').action = btn.getAttribute('data-action');
        document.getElementById('mt-method').value = 'PUT';
        document.getElementById('modal-mt-naslov').textContent = 'Uredi mjesto troška';
        document.getElementById('mt_code').value = btn.getAttribute('data-code') || '';
        document.getElementById('mt_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('mt_legal').value = btn.getAttribute('data-legal') || '';
        document.getElementById('mt_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('mt_to').value = btn.getAttribute('data-to') || '';
    });
    document.getElementById('modal-pravna').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetPravna(); return; }
        document.getElementById('form-pravna').action = btn.getAttribute('data-action');
        document.getElementById('pravna-method').value = 'PUT';
        document.getElementById('modal-pravna-naslov').textContent = 'Uredi pravnu osobu';
        document.getElementById('le_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('le_code').value = btn.getAttribute('data-code') || '';
        document.getElementById('le_oib').value = btn.getAttribute('data-oib') || '';
        document.getElementById('le_parent').value = btn.getAttribute('data-parent') || '';
        document.getElementById('le_street').value = btn.getAttribute('data-street') || '';
        document.getElementById('le_city').value = btn.getAttribute('data-city') || '';
        document.getElementById('le_country').value = btn.getAttribute('data-country') || 'Hrvatska';
        document.getElementById('le_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('le_to').value = btn.getAttribute('data-to') || '';
    });
    document.getElementById('modal-poslovnica').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetPoslovnica(); return; }
        document.getElementById('form-poslovnica').action = btn.getAttribute('data-action');
        document.getElementById('poslovnica-method').value = 'PUT';
        document.getElementById('modal-poslovnica-naslov').textContent = 'Uredi poslovnicu';
        document.getElementById('wc_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('wc_code').value = btn.getAttribute('data-code') || '';
        document.getElementById('wc_legal').value = btn.getAttribute('data-legal') || '';
        document.getElementById('wc_location').value = btn.getAttribute('data-location') || '';
        document.getElementById('wc_street').value = btn.getAttribute('data-street') || '';
        document.getElementById('wc_city').value = btn.getAttribute('data-city') || '';
        document.getElementById('wc_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('wc_to').value = btn.getAttribute('data-to') || '';
    });
    document.getElementById('modal-jedinica').addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-ustroj-edit')) { resetJedinica(btn); return; }
        document.getElementById('form-jedinica').action = btn.getAttribute('data-action');
        document.getElementById('jedinica-method').value = 'PUT';
        document.getElementById('modal-jedinica-naslov').textContent = 'Uredi poslovnu jedinicu';
        document.getElementById('eu_name').value = btn.getAttribute('data-name') || '';
        document.getElementById('eu_parent').value = btn.getAttribute('data-parent') || '';
        document.getElementById('eu_legal').value = btn.getAttribute('data-legal') || '';
        document.getElementById('eu_work').value = btn.getAttribute('data-work') || '';
        document.getElementById('eu_from').value = btn.getAttribute('data-from') || '';
        document.getElementById('eu_to').value = btn.getAttribute('data-to') || '';
    });
})();
</script>
@endpush
