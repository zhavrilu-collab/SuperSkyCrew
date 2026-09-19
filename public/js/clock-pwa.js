(function () {
    var form = document.getElementById('clock-form');
    if (!form) {
        return;
    }

    var cfg = window.HR_CLOCK || {};
    var allowOffline = !!cfg.allowOffline;
    var requirePhoto = !!cfg.requirePhoto;
    var deviceInput = document.getElementById('device-id');
    var eventInput = document.getElementById('client-event-id');
    var offlineInput = document.getElementById('offline');
    var occurredInput = document.getElementById('occurred-at');
    var submitBtn = document.getElementById('clock-submit');
    var flash = document.getElementById('clock-flash');
    var queueBox = document.getElementById('clock-queue');
    var queueList = document.getElementById('clock-queue-list');
    var photoInput = document.getElementById('photo');
    var DB_NAME = 'hr-clock-queue';
    var STORE = 'punches';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function deviceId() {
        var key = 'hr-clock-device';
        var id = localStorage.getItem(key);
        if (!id) {
            id = crypto.randomUUID();
            localStorage.setItem(key, id);
        }
        return id;
    }

    function showFlash(kind, text) {
        if (!flash) {
            return;
        }
        flash.className = 'alert alert-' + kind;
        flash.textContent = text;
        flash.classList.remove('d-none');
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            var req = indexedDB.open(DB_NAME, 1);
            req.onupgradeneeded = function () {
                req.result.createObjectStore(STORE, { keyPath: 'client_event_id' });
            };
            req.onsuccess = function () { resolve(req.result); };
            req.onerror = function () { reject(req.error); };
        });
    }

    function queueAll() {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readonly');
                var req = tx.objectStore(STORE).getAll();
                req.onsuccess = function () { resolve(req.result || []); };
                req.onerror = function () { reject(req.error); };
            });
        });
    }

    function saveQueued(item) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).put(item);
                tx.oncomplete = function () { resolve(); };
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function removeQueued(id) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).delete(id);
                tx.oncomplete = function () { resolve(); };
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    function renderQueue(items) {
        if (!queueBox || !queueList) {
            return;
        }
        queueList.innerHTML = '';
        if (!items.length) {
            queueBox.classList.add('d-none');
            return;
        }
        queueBox.classList.remove('d-none');
        items.sort(function (a, b) { return (a.queued_at || '').localeCompare(b.queued_at || ''); });
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'd-flex justify-content-between border-bottom border-secondary py-2';
            var when = item.occurred_at ? new Date(item.occurred_at) : new Date();
            var time = when.toLocaleTimeString('hr-HR', { hour: '2-digit', minute: '2-digit' });
            li.innerHTML = '<span>' + (item.type_label || item.type) + '</span><span class="text-warning">' + time + '</span>';
            queueList.appendChild(li);
        });
    }

    function refreshQueue() {
        return queueAll().then(renderQueue).catch(function () {
            renderQueue([]);
        });
    }

    function typeLabel(type) {
        if (type === 'in') return 'Prijava';
        if (type === 'out') return 'Odjava';
        if (type === 'break_start') return 'Početak pauze';
        if (type === 'break_end') return 'Kraj pauze';
        return type;
    }

    function readPhoto() {
        if (!photoInput || !photoInput.files || !photoInput.files[0]) {
            return Promise.resolve(null);
        }
        var file = photoInput.files[0];
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = function () { reject(reader.error); };
            reader.readAsDataURL(file);
        });
    }

    function payload(offline, photoData) {
        var type = document.getElementById('punch-type').value;
        return {
            type: type,
            type_label: typeLabel(type),
            from_pwa: 1,
            client_event_id: crypto.randomUUID(),
            device_id: deviceInput.value,
            offline: offline ? 1 : 0,
            occurred_at: new Date().toISOString(),
            latitude: document.getElementById('latitude').value || null,
            longitude: document.getElementById('longitude').value || null,
            gps_accuracy: document.getElementById('gps-accuracy').value || null,
            photo_data: photoData
        };
    }

    function postPunch(data) {
        var body = {
            type: data.type,
            from_pwa: 1,
            client_event_id: data.client_event_id,
            device_id: data.device_id,
            offline: data.offline ? 1 : 0,
            occurred_at: data.occurred_at,
            latitude: data.latitude,
            longitude: data.longitude,
            gps_accuracy: data.gps_accuracy
        };
        if (data.photo_data) {
            body.photo_data = data.photo_data;
        }
        return fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                json.ok = res.ok;
                json.status = res.status;
                return json;
            });
        });
    }

    function syncQueue() {
        if (!navigator.onLine) {
            return Promise.resolve();
        }
        return queueAll().then(function (items) {
            items.sort(function (a, b) { return (a.queued_at || '').localeCompare(b.queued_at || ''); });
            var chain = Promise.resolve();
            items.forEach(function (item) {
                chain = chain.then(function () {
                    return postPunch(item).then(function (json) {
                        if (json.ok || json.status === 200) {
                            return removeQueued(item.client_event_id);
                        }
                        if (json.status === 422 && json.errors && json.errors.client_event_id) {
                            return removeQueued(item.client_event_id);
                        }
                        var msg = json.message || (json.errors && Object.values(json.errors)[0] && Object.values(json.errors)[0][0]);
                        if (msg) {
                            showFlash('danger', msg);
                        }
                        throw new Error('sync-stop');
                    });
                });
            });
            return chain.then(function () {
                if (items.length) {
                    window.location.reload();
                }
            });
        }).catch(function (err) {
            if (err && err.message === 'sync-stop') {
                return refreshQueue();
            }
            return refreshQueue();
        });
    }

    function syncOfflineFlag() {
        var offline = !navigator.onLine;
        if (offlineInput) {
            offlineInput.value = offline ? '1' : '0';
        }
        if (!allowOffline && offline) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Nema mreže';
        } else {
            submitBtn.disabled = false;
        }
    }

    deviceInput.value = deviceId();
    if (eventInput) {
        eventInput.value = crypto.randomUUID();
    }

    form.querySelectorAll('[data-punch-type]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('punch-type').value = btn.getAttribute('data-punch-type');
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var offline = !navigator.onLine;
        if (offline && !allowOffline) {
            showFlash('danger', 'Ova lokacija ne prima naknadnu (offline) prijavu.');
            return;
        }
        if (requirePhoto && photoInput && (!photoInput.files || !photoInput.files[0])) {
            showFlash('danger', 'Lokacija zahtijeva fotografiju prijave.');
            return;
        }

        readPhoto().then(function (photoData) {
            var data = payload(offline, photoData);
            if (occurredInput) {
                occurredInput.value = data.occurred_at;
            }
            eventInput.value = data.client_event_id;
            if (offline) {
                data.queued_at = data.occurred_at;
                return saveQueued(data).then(function () {
                    showFlash('warning', data.type_label + ' spremljena na uređaju. Poslat će se kad bude mreže.');
                    return refreshQueue();
                });
            }
            return postPunch(data).then(function (json) {
                if (json.ok) {
                    window.location.reload();
                    return;
                }
                var msg = json.message;
                if (!msg && json.errors) {
                    var first = Object.values(json.errors)[0];
                    msg = Array.isArray(first) ? first[0] : first;
                }
                showFlash('danger', msg || 'Prijava nije zabilježena.');
            });
        }).catch(function () {
            showFlash('danger', 'Prijava nije zabilježena.');
        });
    });

    window.addEventListener('online', function () {
        syncOfflineFlag();
        syncQueue();
    });
    window.addEventListener('offline', syncOfflineFlag);
    syncOfflineFlag();
    refreshQueue().then(syncQueue);

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('latitude').value = pos.coords.latitude;
            document.getElementById('longitude').value = pos.coords.longitude;
            document.getElementById('gps-accuracy').value = Math.round(pos.coords.accuracy);
        }, function () {}, { enableHighAccuracy: true, timeout: 4000, maximumAge: 30000 });
    }
})();
