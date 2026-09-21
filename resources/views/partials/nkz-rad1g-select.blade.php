<div class="nkz-odabir" data-nkz-odabir>
    <input type="hidden" name="rad1g" id="{{ $id ?? 'pos_rad1g' }}" value="{{ $value ?? '' }}">
    <input type="search"
           class="form-control"
           id="{{ ($id ?? 'pos_rad1g').'_q' }}"
           data-nkz-upit
           placeholder="Traži šifru ili naziv zanimanja"
           autocomplete="off"
           aria-autocomplete="list"
           aria-controls="{{ ($id ?? 'pos_rad1g').'_lista' }}">
    <ul class="nkz-odabir__lista d-none" id="{{ ($id ?? 'pos_rad1g').'_lista' }}" data-nkz-lista role="listbox"></ul>
</div>
<p class="form-text mb-0">Četveroznamenkasta skupina zanimanja prema NKZ-10 (DZS, obrazac RAD-1G). Unos slobodnog teksta nije dopušten.</p>
