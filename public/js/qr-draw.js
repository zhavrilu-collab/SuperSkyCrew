(function () {
    function nacrtaj(el, tekst) {
        if (!el || !tekst || typeof qrcode === 'undefined') {
            return;
        }
        var qr = qrcode(0, 'M');
        qr.addData(tekst);
        qr.make();
        el.innerHTML = qr.createSvgTag(4, 1);
    }

    document.querySelectorAll('[data-qr]').forEach(function (el) {
        nacrtaj(el, el.getAttribute('data-qr'));
    });
})();
