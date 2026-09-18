# 01 — Vizija proizvoda

## Što gradimo

**hr-saas** je multi-tenant HRIS za hrvatske poslodavce: trgovačka društva, obrte i udruge/NPO. Nije dodatak udruga-saas i ne dijeli bazu članova. To je zaseban proizvod na istoj Core platformi.

Poslodavac dobiva:

1. Kadrovsku evidenciju usklađenu s Pravilnikom NN 55/2024.
2. Modernu prijavu i odjavu kroz više kanala.
3. Šihtericu, odsutnosti i godišnji odmor s radnim slijedom odobrenja.
4. Inspekcijski spreman izvoz evidencije radnog vremena.

Super-admin konzola (`multi-tenant console`) ne vodi kadar. Ona otvara tenante, pakete i naplatu. Detalj: [08-platforma-konzola.md](08-platforma-konzola.md).

## Zašto postoji

Hrvatski poslodavac mora voditi evidenciju o radnicima i radnom vremenu (čl. 5. ZOR-a, Pravilnik NN 55/2024). Propuštanje je najteži prekršaj. Tržišni alati (npr. TI 4HR) to pokrivaju kao on-prem Oracle aplikaciju s imenovanim licencama.

Naša ponuda: isti operativni opseg koji HR odjel očekuje (kadar, šihterica, GO, workflow), uz SaaS, moderni clock i zakonski dnevni slog kao proizvodnu značajku, ne kao Excel dodatak.

Usporedba s TI 4HR: [07-usporedba-ti4hr.md](07-usporedba-ti4hr.md).

## Dva tržišta, jedna jezgra

| | Tvrtka / obrt | Udruga / NPO |
| --- | --- | --- |
| Tip organizacije | `company` | `nonprofit` |
| Glavne osobe | radnici (UOR) | radnici + honorarci + volonteri |
| Evidencija RV | obvezna za radnike | obvezna za radnike; volonteri imaju odvojeni sloj sati |
| Dodatno u v1 | kandidati, stranci, olakšice | kartica honorarca; volonter kao status |
| Dodatno kasnije | linije, puni ATS | grant/projektni sati (Faza 2) |

Ista osoba kroz vrijeme može biti kandidat, radnik, pa bivši radnik, ili paralelno honorarac. Statusi: [05-kadar-i-uskladenost.md](05-kadar-i-uskladenost.md).

## Što je uspjeh v1

Kupac koji danas gleda TI 4HR ponudu (kadar + upravljanje vremenom) može preći na hr-saas bez funkcionalne rupe, i dobiva bolju prijavu/odjavu te izvještaj spreman za inspektora rada.

## Što namjerno nismo

- Nismo modul unutar udruga-saas.
- Nismo puni obračun plaća ni JOPPD generator u v1 (sati se izvoze).
- Nismo on-prem Oracle ni imenovane licence po osobi.
- Nismo biometrijski terminal kao zadani kanal.
