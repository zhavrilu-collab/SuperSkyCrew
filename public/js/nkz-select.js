(function () {
    function catalog() {
        var el = document.getElementById('nkz-rad1g-podaci');
        if (!el) return [];
        try {
            var data = JSON.parse(el.textContent || '[]');
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }

    var items = null;
    function all() {
        if (!items) items = catalog();
        return items;
    }

    function labelFor(item) {
        return item.code + ' — ' + item.title;
    }

    function findCode(code) {
        if (!code) return null;
        return all().find(function (item) { return item.code === code; }) || null;
    }

    function bind(root) {
        var hidden = root.querySelector('input[type="hidden"][name="rad1g"]');
        var input = root.querySelector('[data-nkz-upit]');
        var list = root.querySelector('[data-nkz-lista]');
        if (!hidden || !input || !list) return;

        var active = -1;
        var shown = [];

        function close() {
            list.classList.add('d-none');
            list.innerHTML = '';
            active = -1;
            shown = [];
        }

        function markActive() {
            Array.prototype.forEach.call(list.querySelectorAll('.nkz-odabir__stavka'), function (row, i) {
                row.classList.toggle('is-active', i === active);
            });
            var current = list.querySelector('.nkz-odabir__stavka.is-active');
            if (current && current.scrollIntoView) current.scrollIntoView({ block: 'nearest' });
        }

        function setValue(code) {
            var item = code ? findCode(code) : null;
            hidden.value = item ? item.code : '';
            input.value = item ? labelFor(item) : '';
            close();
        }

        root._nkzPostavi = setValue;
        if (hidden.value) setValue(hidden.value);

        function render(query) {
            var needle = (query || '').trim().toLowerCase();
            var selected = findCode(hidden.value);
            if (selected && needle === labelFor(selected).toLowerCase()) {
                needle = '';
            }
            shown = all().filter(function (item) {
                if (!needle) return true;
                return item.code.indexOf(needle) === 0
                    || (item.code + ' ' + item.title).toLowerCase().indexOf(needle) !== -1;
            }).slice(0, 50);

            list.innerHTML = '';
            if (shown.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'nkz-odabir__prazno';
                empty.textContent = all().length === 0
                    ? 'Šifrarnik NKZ-10 nije učitan.'
                    : 'Nema skupine za taj upit.';
                list.appendChild(empty);
            } else {
                shown.forEach(function (item) {
                    var li = document.createElement('li');
                    li.className = 'nkz-odabir__stavka';
                    li.setAttribute('role', 'option');
                    var code = document.createElement('span');
                    code.className = 'nkz-odabir__sifra';
                    code.textContent = item.code;
                    li.appendChild(code);
                    li.appendChild(document.createTextNode(item.title));
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        setValue(item.code);
                    });
                    list.appendChild(li);
                });
                active = 0;
                markActive();
            }
            list.classList.remove('d-none');
        }

        input.addEventListener('focus', function () {
            render(input.value);
        });
        input.addEventListener('input', function () {
            if (input.value.trim() === '') hidden.value = '';
            render(input.value);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                close();
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (list.classList.contains('d-none')) render(input.value);
                active = Math.min(active + 1, shown.length - 1);
                markActive();
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                active = Math.max(active - 1, 0);
                markActive();
            }
            if (e.key === 'Enter' && !list.classList.contains('d-none') && shown[active]) {
                e.preventDefault();
                setValue(shown[active].code);
            }
        });
        input.addEventListener('blur', function () {
            setTimeout(function () {
                close();
                var typed = input.value.trim();
                if (typed === '') {
                    setValue('');
                    return;
                }
                var exact = findCode(typed);
                if (exact) {
                    setValue(exact.code);
                    return;
                }
                var match = typed.match(/^(\d{4})\b/);
                if (match && findCode(match[1])) {
                    setValue(match[1]);
                    return;
                }
                if (hidden.value && findCode(hidden.value)) {
                    input.value = labelFor(findCode(hidden.value));
                    return;
                }
                hidden.value = '';
                input.value = '';
            }, 120);
        });
    }

    window.nkzOdabirPostavi = function (id, code) {
        var hidden = document.getElementById(id);
        var root = hidden && hidden.closest('[data-nkz-odabir]');
        if (root && typeof root._nkzPostavi === 'function') {
            root._nkzPostavi(code || '');
            return;
        }
        if (hidden) hidden.value = code || '';
    };

    document.querySelectorAll('[data-nkz-odabir]').forEach(bind);
})();
