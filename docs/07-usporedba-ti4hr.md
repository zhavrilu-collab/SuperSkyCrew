# 07 — Usporedba s TI 4HR

Izvor: ponuda Osmi BiT d.o.o. **TI 4HR br. 26-26** od 10. 9. 2026., naručitelj Setcor d.o.o. Predmet: moduli **Upravljanje kadrovima (matični podaci)** i **Upravljanje vremenom**, plus mobilna prijava. Stack: Oracle ADF/Apex, Oracle DB, on-prem ili Oracle Cloud. Cijena u ponudi: 45 €/mj admin + 3,80 €/imenovanu licencu (70 korisnika −30 %) = 231,20 €/mj + 840 € usluge.

Ostali TI moduli (plaće, putni nalozi, ZNR, KPI, edukacija, logistika) u katalogu postoje, ali **nisu predmet te ponude**.

## Paritet — moramo imati u v1

| TI 4HR | hr-saas v1 |
| --- | --- |
| Kadrovska kartica „po Pravilniku“ | Kartica čl. 3. + pisani pregled čl. 4. |
| RM, odjeli, MT, lokacije, datum važenja | Isto, stanje na dan |
| Kompetencije, RAD1G, dokumenti PDF | Isto |
| Kandidati / aktivni / bivši, jedan klik | Isto |
| Stranci, isteci UOR/dozvola/pregleda | Isto |
| Word predlošci, uputnica | UOR, rješenje GO, uputnica |
| Matična knjiga, Excel, fluktuacija | Isto |
| Prijava/odjava mobitel + PC gumb | PWA + web ESS |
| Ručni unos admin/voditelj | Isto, s razlogom |
| Naknadni unos s gradilišta | Offline queue + delayed submit |
| Šihterica | Središnji ekran |
| Kalendari 4 razine + smjene + plan → šihterica | Isto |
| Realizirani vs evidencijski sati | Punch/TimeEntry vs evidencijski sloj |
| Šifrarnik prisutnosti/odsutnosti | Isto + seed NN 55/2024 kategorija |
| GO po stažu, staro/novo, rješenje, kalendar | Isto |
| Zaključavanje, fond sati, dnevni odmor | Isto |
| Radni slijed GO/odsutnost/prekovremeni | Generički motor |
| Prijenos u obračun plaća | CSV/API, ne vlastiti obračun |

## Prednost hr-saas (TI slabo ili nema)

- Puni dnevni slog čl. 13. (zastoj, pripravnost, terenski, sve nenazočnosti), ne samo slobodne šifre
- Predaja ovlaštenoj osobi s evidencijom tko/kada/zašto (čl. 5.)
- Retention engine (6 / 40 godina, zatim brisanje)
- Druge FO čl. 10., honorarci, volonteri
- Kiosk, kasnije QR/NFC/terminal/chat
- Geofence, device binding, nepromjenjivi punch
- Live tko je na poslu + queue iznimki
- Inspekcijski paket kao gumb
- SaaS + Core konzola, ne GlassFish/Oracle 11 XE
- Limit aktivnih osoba u paketu, ne imenovana licenca po operateru

## Namjerno ne kopiramo u v1

| TI katalog | Naša odluka |
| --- | --- |
| Obračun plaća | Faza 3 |
| Putni nalozi (GPS, dnevnice, JOPPD) | Faza 3 |
| Puna ZNR (strojevi, okoliš, akti) | Faza 4; v1 samo isteci pregleda |
| KPI, ankete, SMART ciljevi | Faza 4 |
| Upravljanje edukacijom, stipendije | Faza 4 |
| Logistika, prava/povlastice, HTZ, alarm | Faza 4 |
| Interni zahtjev za nabavu kroz workflow | motor to može, UI nije v1 |
| Linije na terminalu | Faza 2, ako kupac traži |
| Imenovane licence 3,80 €/osoba | naši paketi |

## Zaključak

Ne radimo klon TI-ja. Radimo zamjenu za **predmet njihove ponude**, plus zakonski slog i moderne kanale koje Oracle ADF kiosk/mobitel ne pokriva kako treba.
