# 06 — Uloge, tokovi i radni slijed

## Uloge u tenantu

| Uloga | Vidi | Radi |
| --- | --- | --- |
| Vlasnik | sve | postavke organizacije, paketi se čitaju iz konzole |
| HR | sav kadar i šihtericu | kartice, zaključavanje razdoblja, predlošci, predaja inspekciji |
| Računovodstvo | evidencijske sate, izvoz za plaće | nema izmjene puncha bez HR/workflow |
| Voditelj | svoj odjel | ručni unos, odobrenja u slijedu, queue iznimki |
| Radnik | sebe | punch, zahtjevi, uvid, prijava promjene podataka |
| Kiosk | ništa osim punch UI | prijava/odjava na lokaciji |

Platformski super-admin radi **samo u konzoli** (status tenanta, plan). Ne ulazi u kartice radnika. Impersonacija tenanta nije v1 osim ako je već uzorak u SMB — tada se prenosi, s auditom.

Inspekcijski izvoz je akcija HR-a, ne posebna vanjska uloga.

## ESS (radnik)

- Prijava/odjava/pauza
- Danas i tekući tjedan
- Stanje GO (staro/novo)
- Zahtjev: GO, odsutnost, prekovremeni, korekcija puncha
- Uvid u vlastitu evidenciju RV i pisani pregled
- Prijava promjene osobnih podataka (rok 8 dana)

## MSS (voditelj)

- Tko je sada na poslu u odjelu
- Queue iznimki
- Kalendar odsutnosti odjela
- Inbox radnog slijeda
- Ručni punch s razlogom samo za svoje ljude

## HR tokovi

- Zapošljavanje: kandidat → kartica → UOR iz predloška → status employee → početak evidencije RV od datuma početka rada
- Prestanak: datum/razlog → former → retention; evidencija RV prestaje tog dana
- Zatvaranje mjeseca: iznimke riješene → lock → izvoz evidencijskih sati
- Predaja ovlaštenoj osobi: odabir dokumenata → zapis predaje

Onboarding zaduženja imovine (mobitel, alarm, HTZ) **nije v1**.

## Radni slijed (generički motor)

Jedan motor, više vrsta zahtjeva. Nije ad-hoc if/else po ekranu.

Vrste v1:

- `leave_annual` (GO)
- `leave_other` (plaćeni/neplaćeni i ostale nenazočnosti)
- `overtime`
- `punch_correction`

Vrste kasnije (ista tablica): putni nalog, interna nabava.

### Model

- `workflows` — definicija: vrsta, koraci, uvjeti (iznos, broj dana, odjel)
- `workflow_steps` — uloga ili konkretna osoba, redoslijed
- `requests` — podnositelj, payload, status (`draft`, `pending`, `approved`, `rejected`, `cancelled`)
- `request_actions` — tko, kada, odobri/odbij, komentar

Pravila:

- E-mail na svaki korak s dubinskim linkom na inbox (ne na vanjski login ako sesija postoji).
- Odbijanje vraća na prethodni korak ili podnositelju, uz obavezan razlog.
- Povijest je nepromjenjiva.
- Višestruko uvjetovanje: npr. GO do 3 dana odobrava voditelj, dulje HR.
- Kontrola maksimuma plaćenog dopusta pri podnošenju.
- Nakon odobrenja GO: generiraj rješenje, umanj fond, upiši šihtericu.
- Nakon odobrenja korekcije puncha: novi Punch vezan na stari, preracun TimeEntry.

Korisnik (HR/vlasnik) vidi popis slijedova i može mijenjati korake. Grafički designer nije v1; tablica koraka jest.

### Notifikacije v1

- E-mail: zahtjev čeka, odobreno, odbijeno (radni slijed odmah); istek dokumenta, zaboravljena odjava, nekompletan slog 5. i 7. dana (`hr:reminders`, jednom po događaju)
- Push: Faza 2

## Autorizacija podataka

- Voditelj ne vidi plaće ni OIB cijelog poduzeća — samo svoj odjel, i to operativna polja.
- Računovodstvo vidi evidencijske sate i MT, ne CV niti zdravstvene nalaze.
- Izvoz inspekciji je privilegirana akcija s auditom (čl. 5. st. 6.). Revizijski trag: Postavke → Podaci → Revizijski trag.
