# Includes Functions TODO

## Paskirtis
- Mažesni domeniniai helperiai, pvz. įrašai, šūktelėjimai ir puslapiavimas.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: tik locale ir suderinamumo tvarkymas.
- `v1.2`: helperiu skaidymas i paslaugas.
- `v1.3`: tik tie helperiai, kurie turi likti del patogumo ar suderinamumo.

## Svarbūs failai
- `posts.php`: įrašų kūrimas, atnaujinimas ir trynimas.
- `shouts.php`: senasis shout helperis dėl suderinamumo.
- `pagination.php`: puslapiavimo logika.
- `output.php`: pagalbinės išvedimo funkcijos.

## Likę darbai
- [ ] Nuspręsti, kiek ilgai dar laikyti senus `posts/shouts` helperius suderinamumui.
- [ ] Sudėti daugiau locale raktų į senesnius helperius, jei jie dar naudojami.
- [ ] Jei bus pereinama prie OOP, šituos helperius perkelti į aiškias paslaugas.
