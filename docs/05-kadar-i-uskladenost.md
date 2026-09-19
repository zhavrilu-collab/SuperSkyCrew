# 05 — Kadar i usklađenost

Tri zakonske evidencije (Pravilnik NN 55/2024, čl. 2.):

1. Radnici na ugovoru o radu (čl. 3.)
2. Druge fizičke osobe (čl. 10.)
3. Radno vrijeme (vidi [03](03-evidencija-radnog-vremena.md))

Plus operativni registri koje TI kupac očekuje: kandidati, bivši radnici, honorarci, volonteri.

## Model osobe

Jedna `people` kartica po fizičkoj osobi u tenantu. Statusi kroz vrijeme (`person_engagements`):

| Status | ZOR evidencija radnika | Evidencija RV | Napomena |
| --- | --- | --- | --- |
| `candidate` | ne | ne | ATS-lite |
| `employee` | da | da | UOR |
| `assigned` | da (uži skup) | da ako je ugovoreno | agencija / povezano društvo |
| `other_fo` | evidencija čl. 10. | trajanje rada čl. 22. | student, učenik, SOR, rad za opće dobro |
| `contractor` | ne | evidencija rada (interno) | UOD, autorski, honorar |
| `volunteer` | ne | ne (grant sati Faza 2) | udruge |
| `executive` | da | čl. 21. iznimka | ako je ugovorena samostalnost |
| `former` | arhiva, retention | arhiva | prestao angažman |

Prijenos kandidat → radnik jednim klikom nakon potpisa UOR (paritet TI). Aktivni → bivši na datum prestanka.

## Kartica radnika (čl. 3. st. 1.)

1. Ime i prezime
2. OIB
3. Spol
4. Datum rođenja
5. Državljanstvo
6. Prebivalište / boravište
7. Dozvola za boravak i rad ili potvrda o prijavi rada (treće zemlje)
8. Stručno obrazovanje, ispiti, tečajevi, licence, certifikati (uvjet za posao)
9. Datum početka rada
10. Naziv radnog mjesta / vrsta rada
11. Vrsta ugovora o radu
12. Datum i razlog prestanka
13. Datumi prijave/promjene/prestanka na obvezna osiguranja (uključujući DMO ako poslodavac sudjeluje, i OZO u inozemstvu)

Čl. 3. st. 2. — drugi podaci od kojih ovise prava: olakšice, podaci za plaće (iban, koeficijent, dodaci u %), staž, djeca za GO, rodiljna/roditeljska prava kao status, ZNR obveza pregleda. Unos, ne obračun.

Pisani pregled (čl. 4.) je PDF/ispis točaka 1–13 za svakog radnika, uključujući ustupljene.

## Organizacija

- Lokacije
- Odjeli
- Radna mjesta (kompetencije, RAD1G, kriteriji GO, raspon)
- Mjesta troška
- Svaka veza ima `valid_from` / `valid_to` → pregled **stanja na dan**

Fluktuacija: izvještaj ulazaka/izlazaka po razdoblju. Matična knjiga: ispis registra aktivnih.

## Ugovori i dokumenti

Tipovi dokumenata korisnik definira (UOR, aneks, UOD, viza, porezna kartica, certifikat, uputnica…). Više datoteka po osobi.

Predlošci v1 (Word merge u `.docx`, HTML ispis ostaje na kartici):

- **Ugovor o radu** — ugrađeni predložak; Preuzmi ili Spremi u dosje
- **Rješenje o GO** — HTML na odobrenom zahtjevu; Word se upisuje u dosje pri odobrenju
- **Uputnica za liječnički pregled** — ugrađeni predložak

Polja su `{{ime}}`, `{{go_broj}}` i ostali tokeni u Postavke → Kadrovi → Predlošci. Word često reže token u više runova — merge ih spaja. Korisnik dodaje vlastite predloške. Ugrađeni se ne brišu.

Upozorenja isteka: UOR na određeno, boravište/dozvola, certifikat, liječnički, atest. Signal na dashboardu HR-a (npr. 5/10/20/30 dana).

## Dosje i rokovi čuvanja (čl. 8.–9.)

Klasifikacija isprave određuje retention. Motor ne briše ručno; job predlaže brisanje nakon isteka, HR potvrđuje.

| Klasa | Rok (minimum) |
| --- | --- |
| Pisani pregled | do isteka godine prestanka RO |
| UOR i aneksi | 6 godina od isteka godine prestanka |
| Sporazumi, otkaz | 6 godina od prestanka |
| Tiskanice prijave osiguranja | 6 godina od prestanka |
| Obrazovanje / dokazi | 6 godina od prestanka |
| Plaće, porez, doprinosi | posebni propisi (proračun: obračun trajno / 11 god.) |
| ZNR, ozljede | posebni propisi |
| Mirovina / staž s povećanjem | **40 godina** od prestanka |
| Zdravstveno / skrb / rodiljno | 6 godina od nastanka |
| Zahtjevi za zaštitu prava | 6 godina od nastanka |
| KU / pravilnik o radu | 6 godina od prestanka važenja |
| Ostalo | 6 godina od nastanka |
| Druge FO (čl. 12.) | 6 godina od prestanka rada |

Ako je pokrenut radni spor, čuvanje traje do pravomoćnog okončanja. Nakon isteka podaci se brišu ili uklanjaju (čl. 9. st. 5.).

Preslika osobne **nije** klasa koju seedamo kao obveznu.

## Predaja ovlaštenoj osobi (čl. 5. st. 5.–6.)

Svako preuzimanje pisanog dokumenta (papir ili e-oblik) bilježi: datum, svrhu, ovlaštenu osobu/tijelo, što je predano. To je dio inspekcijskog paketa.

Radnik ima pravo uvida u vlastite podatke (čl. 5. st. 1.). Promjenu prijavljuje u 8 dana (čl. 5. st. 2.) — ESS forma „prijava promjene“.

## Kandidati (ATS-lite, v1)

- Osobni podaci, škole, kompetencije, CV i bilješke razgovora (kartica **Odabir**; ostaje i nakon prijenosa u kadar)
- Stranci: ima/nema radnu i boravišnu, rok (Osobno)
- Nije puni ATS (natječaji, scoring) — to je Faza 4

## Ustupljeni, rukovodeće i volonteri

- **Ustupljeni radnik** — kartica čl. 3./4. s nazivom ustupitelja (agencija / povezano društvo). Evidencija RV samo ako je ugovorena.
- **Rukovodeća osoba** — kartica čl. 3. Ako je ugovorena samostalnost (čl. 21.), šihterica ne diže iznimke dnevnog odmora, kašnjenja ni mjesečnog fonda.
- **Volonter** — Postavke → Kadrovi → Volonteri. Nije u matičnoj knjizi ni na šihterici. Grant sati Faza 2.

## Druge FO (čl. 10.)

Seed kategorija: SOR bez RO, studenti, učenici povremeni rad, učenje temeljeno na radu, djeca/maloljetnici uz naplatu (ako je poslodavac organizator), rad za opće dobro.

Kartica čl. 10. st. 2.: ime, OIB, spol, rođenje, državljanstvo, prebivalište, naziv ugovora/akta, mjesto rada, početak, prestanak, prijave osiguranja ako postoje.

## Što v1 ne radi u kadru

- e-prijava HZMO XML (ručni datum + podsjetnik)
- Puna ZNR knjiga ispitivanja strojeva/okoliša
- Zaduženje imovine / HTZ opreme (TI logistika)
