# 04 — Kanali prijave i odjave

Svi kanali zovu **isti Clock API**. Kanal je metapodatak na Punchu. Pravila (geofence, PIN, foto, offline) žive na lokaciji i/ili osobi, ne u klijentu.

UX cilj: prijava u **2 sekunde**, jedno veliko stanje (nisi prijavljen / na pauzi / na terenu / odjavljen), tjedni pregled sati, zahtjev za ispravak.

## Kanali

| Prioritet | Kanal | v1 | Napomena |
| --- | --- | --- | --- |
| P0 | Mobilna PWA | da | Glavni kanal. GPS, geofence, offline queue, kasnije push |
| P0 | Web ESS | da | Isti račun, 1-tap gumb na desktopu |
| P0 | Kiosk / dijeljeni tablet | da | PIN ili QR osobe + QR lokacije |
| P0 | Ručni unos voditelja/HR | da | Razlog obavezan |
| P1 | QR/NFC na ulazu | ne | Skener u PWA ili kiosku |
| P1 | Fizički terminal | ne | Adapter na Clock API (ZKTeco i sl.) |
| P2 | Chat (WhatsApp/Telegram/Viber) | ne | Tekst „prijava“/„odjava“ za teren |
| P2 | Kalendar/ICS | ne | Samo plan, nikad zakonski punch |
| Kasnije | Biometrija | ne | Samo uz DPIA; nije default |

Feature flagovi paketa: `clock_mobile`, `clock_kiosk`, `clock_geofence`. Terminal i chat pali konzola kad uđe Faza 2.

## Clock API (koncept)

`POST /{slug}/api/clock/punches`

Tijelo: `type`, `occurred_at`, `channel`, `device_id`, `location_id`, GPS, `offline`, opcionalni foto, kiosk token/PIN.

Odgovor: novo stanje osobe, otvoreni TimeEntry, eventualna iznimka (`geofence_fail`, `already_in`, …).

Idempotencija: klijent šalje `client_event_id`; duplikat se ne dupla.

Auth:

- ESS/PWA: session ili token radnika (Core identity).
- Kiosk: uređaj vezan na lokaciju + PIN/QR osobe; punch `created_by` = kiosk, `person_id` = skenirana osoba.
- Voditelj: session + razlog.

## Pravila po lokaciji

Svaka lokacija ima:

- `geofence`: isključeno / upozorenje / strogo (radius u metrima)
- `require_photo`: da/ne
- `allow_offline`: da/ne
- `kiosk_enabled`
- `allowed_channels`

Rad na daljinu: geofence isključen; bilježi se IP i uređaj; samopotvrda. Poslodavac i dalje nadzire ažurnost (čl. 17.).

Teren/gradilište: offline queue na PWA; sync kad se vrati mreža; `occurred_at_device` ostaje original.

## Antiprevara (proporcionalno, GDPR)

- Geofence po lokaciji, ne globalno.
- Opcionalni selfie: kratko čuvanje, bez face-matchinga (to bi bila biometrija).
- Device binding za PWA; upozorenje na mock GPS ako je detektabilan.
- Tuđa prijava zabranjena osim kioska s PIN-om.
- Korekcija samo kroz workflow, nikad tihi edit puncha.

Ne radimo otisak prsta/lice u v1.

## Klijenti

**PWA (diferencijator)**

- Instalabilna, mobilni first, radi offline.
- Jedan gumb prijava/odjava, pauza odvojeno.
- Vidljivo: danas odrađeno, tjedni fond, otvorene iznimke, zahtjev za ispravak.

**Web ESS**

- Ista stanja u back-office layoutu ili uskom clock viewu.
- Nije tablica šihterice.

**Kiosk**

- Cijeli zaslon, veliki PIN pad / skener.
- Odjava iste osobe na istom uređaju.
- Auto-logout nakon puncha.

**Back-office šihterica**

- Nije kanal. Čita punchove. Ručni unos je zasebna akcija s razlogom.

## Vanjski prijenos

TI nudi „prijenos iz drugih sustava na zahtjev“. Kod nas: isti Clock API. Faza 2 terminal adapter je prvi integracijski klijent. Nema posebnog CSV importa punchova u v1 (rizik od tihog krivotvorenja); HR unosi korekciju.
