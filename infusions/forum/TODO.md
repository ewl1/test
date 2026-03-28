# Forum TODO

## Paskirtis
- Pilnas forumo modulis su kategorijomis, forumais, subforumais, temomis, atsakymais ir moderavimu.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: baziniai moderatoriu ir saugumo patobulinimai.
- `v1.2`: logikos skaidymas i klases.
- `v1.3`: moderavimo istorija, UX ir bendruomenes funkcijos.

## Svarbūs failai
- `bootstrap.php`: schema, CRUD, moderavimo logika, BBCode ir smailai.
- `admin.php`: forumo struktūros valdymas.
- `classes/ForumService.php`: modulio servisas, skirtas temų ir atsakymų darbams per `App\\Forum\\`.
- `assets/css/forum.css`, `assets/js/forum.js`: modulio UI assetai.
- `locale/lt.php`: forumo tekstai ir pranešimai.

## Likę darbai
- [x] CSS perkeltas į `assets/css/forum.css`.
- [x] JS perkeltas į `assets/js/forum.js`.
- [x] Modulio tekstai perkelti į `locale/lt.php`.
- [x] `App\\Forum\\ForumService` perkeltas į `classes/`.
- [ ] Pridėti temų ir atsakymų paieškos filtrus pačiame forume.
- [ ] Pridėti moderavimo istoriją ir „redagavo“ audit detalėse.
- [ ] Išskaidyti daugiau forumo logikos iš `bootstrap.php` į atskiras modulio klases.
