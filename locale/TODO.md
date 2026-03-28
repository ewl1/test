# Locale TODO

## Paskirtis
- Branduolio kalbų failai ir bendra lokalizacijos strategija.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: pilnas antriniu admin ir paieskos puslapiu perkelimas.
- `v1.2`: namespace ir helperiu stabilizavimas aplink locale sluoksni.
- `v1.3`: papildomos kalbos ir aktyvios kalbos pasirinkimas administracijoje.

## Svarbūs failai
- `lt.php`: pagrindiniai branduolio, auth, profile ir validation tekstai.
- `../includes/locale.php`: locale užkrovimas branduoliui ir infusion moduliams.

## Likę darbai
- [x] Pridėtas pradinis `lt` locale failas.
- [x] Pridėtas modulių locale užkrovimas per `infusions/<modulis>/locale/`.
- [~] Antriniai admin ir paieškos puslapiai palaipsniui perkeliami į locale raktus.
- [ ] Paruošti struktūrą papildomoms kalboms (`en`, `pl` ar kt.), jei jų reikės.
- [ ] Pridėti nustatymą administracijoje aktyviai kalbai pasirinkti.
