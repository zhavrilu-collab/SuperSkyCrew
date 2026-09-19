(function () {
    const form = document.getElementById('qr-form');
    const input = document.getElementById('qr');
    const video = document.getElementById('qr-video');
    const btn = document.getElementById('qr-kamera');
    const pin = document.getElementById('pin');
    if (!form || !input) {
        return;
    }

    let stream = null;
    let timer = null;
    let buffer = '';

    function zaustaviKameru() {
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
            stream = null;
        }
        if (video) {
            video.classList.add('d-none');
            video.srcObject = null;
        }
    }

    function posalji(tekst) {
        const vrijednost = (tekst || '').trim();
        if (!vrijednost) {
            return;
        }
        input.value = vrijednost;
        zaustaviKameru();
        form.submit();
    }

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            posalji(input.value);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (document.activeElement === pin || document.activeElement === input) {
            return;
        }
        if (e.key === 'Enter') {
            if (buffer.length >= 16) {
                posalji(buffer);
            }
            buffer = '';
            return;
        }
        if (e.key.length === 1) {
            buffer += e.key;
            clearTimeout(timer);
            timer = setTimeout(function () { buffer = ''; }, 120);
        }
    });

    if (!btn || !video) {
        return;
    }

    btn.addEventListener('click', async function () {
        if (!('BarcodeDetector' in window) || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            btn.textContent = 'Kamera nije dostupna';
            btn.disabled = true;
            input.focus();
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
                audio: false,
            });
        } catch (err) {
            btn.textContent = 'Kamera odbijena';
            input.focus();
            return;
        }

        video.srcObject = stream;
        video.classList.remove('d-none');
        await video.play();
        btn.classList.add('d-none');

        const detector = new BarcodeDetector({ formats: ['qr_code'] });
        const petlja = async function () {
            if (!stream) {
                return;
            }
            try {
                const kodovi = await detector.detect(video);
                if (kodovi.length && kodovi[0].rawValue) {
                    posalji(kodovi[0].rawValue);
                    return;
                }
            } catch (err) {
                // sljedeći kadar
            }
            requestAnimationFrame(petlja);
        };
        requestAnimationFrame(petlja);
    });
})();
