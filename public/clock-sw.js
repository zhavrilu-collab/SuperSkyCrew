const CACHE = 'hr-clock-v1';

self.addEventListener('install', function (event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', function (event) {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }
    const url = new URL(request.url);
    const clockAsset = url.pathname === '/clock-sw.js'
        || url.pathname === '/js/clock-pwa.js'
        || url.pathname.endsWith('/prijava')
        || url.pathname.endsWith('/prijava/manifest.webmanifest');
    if (!clockAsset) {
        return;
    }
    event.respondWith(
        fetch(request).then(function (response) {
            const copy = response.clone();
            caches.open(CACHE).then(function (cache) {
                cache.put(request, copy);
            });
            return response;
        }).catch(function () {
            return caches.match(request);
        })
    );
});
