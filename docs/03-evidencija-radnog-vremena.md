# 03 — Evidencija radnog vremena

Zakonski okvir: Pravilnik NN 55/2024, čl. 13.–21. Evidencija se vodi za obračunsko razdoblje plaće. Unos najkasnije **7. dana** od dana na koji se podatak odnosi. Čuvanje **6 godina** od isteka godine u kojoj je dokumentacija nastala (ili do pravomoćnog okončanja radnog spora).

Početak i završetak rada (čl. 13. st. 1. t. 3.–4.) zakonski su obvezni samo ako je to ugovoreno (KU, sporazum s RV, UOR, pravilnik). Proizvod **uvijek** bilježi punch. Zakonski izvještaj pokazuje početak/kraj samo kad je organizacija to uključila.

Obveza vrijedi i za rad na izdvojenom mjestu i rad na daljinu (čl. 16.–17.).

## Tri sloja, ne jedan

| Sloj | Namjena | Mijenja se? |
| --- | --- | --- |
| **Punch** | Sirovi događaj prijave/odjave/pauze | Nikad overwrite; korekcija = novi događaj + odobrenje |
| **TimeEntry** | Dnevni slog čl. 13. (compliance) | `draft` → `complete` → `locked`; locked samo storno |
| **Evidencijski sati** | Sati za vanjski obračun plaće | Izvedeno iz TimeEntry + šifrarnika; HR može korigirati prije zaključavanja |

TI 4HR razlikuje realizirane i evidencijske sate. Kod nas: Punch/TimeEntry = realizacija, evidencijski sloj = plaća.

Projektni/grant sati (Faza 2) su četvrti sloj i **ne smiju** mijenjati TimeEntry.

## Punch

Obvezna polja:

- `organization_id`, `person_id`
- `type`: `in` / `out` / `break_start` / `break_end`
- `occurred_at_device`, `occurred_at_server`
- `channel`, `device_id` (ako postoji)
- `location_id` (ako je vezan na lokaciju)
- GPS lat/lng + točnost (ako kanal šalje)
- `geofence_result`: `pass` / `fail` / `skipped`
- `offline`: bool
- `photo_id` (opcionalno, kratki retention)
- `raw_payload`
- `integrity_hash` (nepromjenjivost)
- `created_by` (osoba ili sustav)
- `correction_of_id` + `reason` + `workflow_id` ako je korekcija

Pravila:

- Jedna aktivna prijava po osobi (nema overlapping in bez out, osim iznimke u queueu).
- Ručni unos voditelja uvijek ima razlog i audit (`audit_events`).
- Naknadni unos s terena (gradilište bez mreže) ide kao offline queue ili delayed submit; `occurred_at_device` ostaje trenutak događaja.

## TimeEntry (dnevni slog čl. 13.)

Jedan red po osobi po kalendarskom datumu u obračunskom razdoblju.

Obvezna/izvještajna polja:

1. Ime i prezime (denormalizirano na izvozu)
2. Datum u mjesecu
3. Početak rada
4. Završetak rada
5. Vrijeme i sati zastoja / prekida krivnjom poslodavca ili okolnostima za koje radnik nije odgovoran
6. Ukupno dnevno radno vrijeme
7. Sati terenskog rada
8. Sati pripravnosti
9. Nenazočnost:
   - odmor (dnevni, tjedni, godišnji)
   - neradni dani i blagdani
   - privremena nesposobnost (bolovanje)
   - plaćeni dopust i odsutnost s rada
   - očinski dopust i dopust drugog posvojitelja
   - neplaćeni dopust za osobnu skrb
   - neplaćeni dopust kandidata (predsjednik, sabor, županije, grad, općina)
   - nenazočnost po zahtjevu radnika
   - nenazočnost krivnjom radnika
   - vojna obveza / ugovorna pričuva
   - štrajk
   - lockout

Posebni sati (čl. 13. st. 2.), uvijek se vode jer ovise prava iz radnog odnosa:

- noć (22:00–06:00)
- prekovremeni
- smjenski
- dvokratni
- blagdan / zakonom utvrđeni neradni dan
- nedjelja

Status: `draft` (dan u tijeku) → `complete` (parovi zatvoreni ili odsutnost popunjena) → `locked` (HR zatvorio razdoblje ili prošao rok 7 dana uz automatiku + upozorenje).

Izuzeci:

- Jednako raspoređeno vrijeme: nije obvezno evidentirati dnevni/tjedni odmor.
- Ako se vode početak i kraj, nije obvezan dnevni/tjedni odmor.
- Rukovodeća osoba (čl. 21.): smanjeni skup ako je ugovorena samostalnost; inače puni slog.
- Druge FO (čl. 22.): evidencija trajanja rada, uži skup polja.

## Motor pravila

Pokreće se nakon puncha i noćnim jobom.

1. Spoji parove in/out i pauze.
2. Izračunaj ukupno, noć, prekovremene iznad ugovorenog dnevnog/tjednog fonda.
3. Upiši TimeEntry.
4. Označi iznimke:
   - missing out
   - overlapping
   - punch izvan geofence
   - kašnjenje vs plan/raspored
   - rad na blagdan/nedjelju
   - narušen dnevni odmor
   - odstupanje od mjesečnog fonda

Voditelj vidi queue iznimki za svoj odjel. HR zaključava razdoblje i generira izvještaj NN 55/2024.

## Šihterica

Središnji ekran back-officea: retci = osobe (filtar odjel/lokacija), stupci = dani razdoblja, ćelija = šifre sati + ukupno.

- Korisnički šifrarnik vrsta prisutnosti/odsutnosti (kratica mora imati pisano značenje, čl. 18. st. 2.).
- Klik na ćeliju otvara punchove tog dana, TimeEntry i evidencijske sate.
- Plan se može prenijeti u šihtericu (dan / tjedan / mjesec).
- Zaključano razdoblje je read-only osim storna.

## Kalendari i smjene

Četiri razine, prva specifičnija pobjeđuje:

1. Radnik
2. Radno mjesto
3. Odjel
4. Organizacija (default + blagdani RH)

Svaki kalendar ima smjene (početak, kraj, pauza, noćna oznaka). Plan rada po radniku/odjelu šalje se e-mailom. Prijava se uspoređuje s planom za kašnjenje, ali punch se ne odbija samo zato što je izvan plana (osim stroge geofence na lokaciji).

## Godišnji odmor i odsutnosti

- Kriteriji fonda: Postavke → Vrijeme → **GO politika**. Fond = max(osnova organizacije, fond radnog mjesta) + dodatak za najviši dosegnuti prag staža + dani × broj djece. Staž = mjeseci kod poslodavca + staž prije s kartice. Kartica može ostati ručna.
- Stari GO prije novog (preneseni dani iz prethodne godine).
- Zahtjev ide u radni slijed; nakon odobrenja generira se rješenje (predložak) i upis u šihtericu.
- Kalendar odsutnosti: prava uvida, vrste, statusi.
- Bolovanje: teret poslodavca vs HZZO kao šifre; ne radimo medicinski modul.
- Sve odsutnosti iz čl. 13. st. 1. t. 9. moraju postojati u šifrarniku (seed), korisnik može dodati vlastite.

## Izvještaji v1

- Dnevni/mjesečni slog NN 55/2024 (PDF + Excel), po radniku i zbirno
- Pregled za radnika (pravo uvida, čl. 20. + čl. 5.)
- Inspekcijski paket: pisani pregled + slog RV + evidencija predaje
- Stanje GO po godini i radniku
- Odstupanje od mjesečnog fonda
- Tko je trenutno na poslu
- Priprema sati za plaće (CSV/API): evidencijske šifre × sati × MT

## Jobovi

- Zatvaranje jučerašnjeg dana, oznaka missing out (pri rebuildu sloga)
- `hr:reminders` (dnevno): istek dokumenata, zaboravljena odjava, nekompletan slog 5. i 7. dana
- Upozorenje dnevnog odmora (iznimka na slogu)
- Zaključavanje razdoblja po kalendaru plaće organizacije
