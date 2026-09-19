# 02 — Katalog modula i faze

v1 se izjednačuje s predmetom ponude TI 4HR (kadar + vrijeme + workflow) i nadmašuje kanale prijave te slog NN 55/2024. Go-live nije „samo clock“.

## Faza 0 — temelj

Uvjet za sve ostalo.

- Multi-tenant organizacija, lokacije, odjeli, radna mjesta, mjesta troška
- Datum važenja strukture (pregled stanja na dan)
- Uloge: vlasnik, HR, računovodstvo, voditelj (svoj odjel), radnik
- Revizijski trag, GDPR (minimizacija, uvid, retention)
- Hrvatski UI, blagdani RH, OIB
- Generički radni slijed (višekorak, e-mail s linkom, odbijanje s razlogom)

Ne spremati presliku osobne iskaznice osim uz zaseban pravni temelj (AZOP).

## Faza 1 — v1 (go-live)



### Kadar

- Kartica čl. 3., ugovori i aneksi, probni rad, olakšice, podaci za plaće (unos, ne obračun)
- RAD1G, kompetencije, obrazovanje
- Kandidati / aktivni / bivši, prijenos jednim klikom
- Stranci: dozvola i boravište s rokom
- Dosje, Word/PDF predlošci (UOR, rješenje GO, uputnica za liječnički)
- Upozorenja isteka (UOR na određeno, dozvole, pregledi, certifikati)
- Matična knjiga, Excel, pregled na dan
- Pisani pregled (čl. 4.) i predaja ovlaštenoj osobi (čl. 5.)
- Druge FO čl. 10. i honorarci (minimum kartice)
- Volonter kao status; grant sati nisu v1

Detalj: [05-kadar-i-uskladenost.md](05-kadar-i-uskladenost.md).

### Vrijeme

- Šihterica kao središnji ekran
- Korisnički šifrarnik vrsta sati i odsutnosti
- Punch kanali P0: PWA, web, kiosk, ručni unos + offline/naknadni unos
- Realizirani vs evidencijski sati
- Puni dnevni slog čl. 13. + posebni sati (noć, prekovremeni, smjena, dvokratni, blagdan, nedjelja)
- Kalendari na 4 razine + smjene; plan → šihterica; e-mail plana
- GO: kriteriji/staž, staro pa novo, rješenje, kalendar
- Zaključavanje razdoblja, kontrola mjesečnog fonda (ugovoreni tjedni sati), upozorenje dnevnog odmora
- Live dashboard (tko je na poslu, iznimke)
- Inspekcijski izvoz; CSV/API priprema za vanjski obračun (šifra × sati × MT)
- Geofence i nepromjenjivi punch

Detalj: [03-evidencija-radnog-vremena.md](03-evidencija-radnog-vremena.md), [04-kanali-prijave.md](04-kanali-prijave.md).

### Workflow

Zahtjevi za GO, ostale odsutnosti, prekovremeni i korekciju puncha. Detalj: [06-uloge-i-tokovi.md](06-uloge-i-tokovi.md).

## Faza 2 — širina kanala i NPO

- QR/NFC, adapter za fizički terminal, chat kanal
- Zamjene smjena, otvorene smjene, grace/zaokruživanje
- Tjedni limiti ZOR-a kao upozorenja (ne slijepa blokada)
- Projektni / grant sati (udruge), odvojeni od zakonskog sloga
- Linije (proizvodnja) samo ako kupac traži
- Push obavijesti



## Faza 3 — naknade

- Vlastiti obračun plaće i JOPPD samo ako se kasnije odluči
- Putni nalozi / veza na terenske sate



## Faza 4 — talent i ZNR platforma

```
Puni ATS, onboarding/offboarding zaduženja imovine
```

```
KPI, ankete, LMS
```

```
Puna ZNR evidencija ispitivanja i radnog okoliša (u v1 samo isteci pregleda na kartici)
```



## Out-of-scope za v1

- Payroll engine, JOPPD XML, e-prijava HZMO
- Putni nalozi
- Puna ZNR, KPI, LMS, logistika/zaduženja
- Biometrija kao default
- Dijeljenje osoba s udruga-saas
- Imenovane licence po osobi (TI model)

Arhitektura ostavlja mjesta: šifrarnici, dokumenti i sati su proširivi bez migracije jezgre.