# Forum TODO

## Paskirtis
- Pilnas forumo modulis su kategorijomis, forumais, subforumais, temomis, atsakymais ir moderavimu.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Svarbūs failai
- `bootstrap.php`: schema, CRUD, moderavimo logika, BBCode ir smailai.
- `admin.php`: forumo struktūros valdymas.
- `assets/css/forum.css`, `assets/js/forum.js`: modulio UI assetai.
- `locale/lt.php`: forumo tekstai ir pranešimai.

## Likę darbai
- [x] CSS perkeltas į `assets/css/forum.css`.
- [x] JS perkeltas į `assets/js/forum.js`.
- [x] Modulio tekstai perkelti į `locale/lt.php`.
- [ ] Pridėti temų ir atsakymų paieškos filtrus pačiame forume.
- [ ] Pridėti moderavimo istoriją ir „redagavo“ audit detalėse.
- [ ] Apsvarstyti atskirą `ForumService` klasę, jei forumas toliau augs.
