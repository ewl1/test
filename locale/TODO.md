# Locale TODO

## Paskirtis
- Branduolio ir moduliu kalbu failai, locale uzkrovimas ir kalbu strategija.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: pilnas admin, paieskos ir diagnostikos tekstu perkelimas.
- `v1.2`: komunikacijos, profilio ir pranesimu centro tekstai.
- `v1.3`: forumo ir naujienu papildomu funkciju tekstai.
- `v1.5`: papildomos kalbos ir aktyvios kalbos pasirinkimas.

## Svarbus failai
- `lt.php`: pagrindiniai branduolio, auth, profile ir validation tekstai.
- `../includes/locale.php`: locale uzkrovimas branduoliui ir moduliams.
- `../infusions/<modulis>/locale/`: modulio specifiniai tekstai.

## Likusieji darbai
- [x] Pridetas pradinis `lt` locale failas.
- [x] Pridetas moduliu locale uzkrovimas per `infusions/<modulis>/locale/`.
- [~] Antriniai admin ir paieskos puslapiai palaipsniui perkeliami i locale raktus.
- [ ] Uzbaigti likusiu admin puslapiu locale perkelima.
- [ ] Prideti locale raktus asmeninems zinutems ir pranesimu centrui.
- [ ] Prideti locale raktus perrasytam profiliui ir `security` skirtukui.
- [ ] Prideti locale raktus versijoms, update channel ir update center.
- [ ] Prideti locale raktus SEO audit, redirect manager ir broken links checker puslapiams.
- [ ] Prideti locale raktus naujienu papildomoms funkcijoms: tagams, planuotam publikavimui, RSS, SEO laukams.
- [ ] Paruosti struktura papildomoms kalboms (`en`, `pl` ar kt.), jei ju reikes.
- [ ] Prideti nustatyma administracijoje aktyviai kalbai pasirinkti.
