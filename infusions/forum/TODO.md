# Forum TODO

## Paskirtis
- Pilnas forumo modulis su kategorijomis, forumais, subforumais, temomis, atsakymais ir moderavimu.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: anti-spam, teises ir moderatoriu patikimumas.
- `v1.3`: funkciju brandinimas ir bendruomenes sluoksnis.
- `v1.4`: logikos skaidymas i papildomas modulio klases.

## Svarbus failai
- `bootstrap.php`: schema, CRUD, moderavimo logika, BBCode ir smailai.
- `admin.php`: forumo strukturos valdymas.
- `classes/ForumService.php`: pagrindinis modulio servisas.
- `assets/css/forum.css`, `assets/js/forum.js`: modulio UI assetai.
- `locale/lt.php`: forumo tekstai ir pranesimai.

## Likusieji darbai
- [x] CSS perkeltas i `assets/css/forum.css`.
- [x] JS perkeltas i `assets/js/forum.js`.
- [x] Modulio tekstai perkelti i `locale/lt.php`.
- [x] `App\\Forum\\ForumService` perkeltas i `classes/`.
- [ ] Prideti temoms ir atsakymams paieskos filtrus paciame forume.
- [ ] Prideti moderavimo istorija ir `redagavo` informacija.
- [ ] Prideti `unread tracking`.
- [ ] Prideti priedus (`attachments`).
- [ ] Prideti `mention` sistema.
- [ ] Prideti `like / reaction` sistema.
- [ ] Prideti report/mygtukus ir jungti su bendra `moderation queue`.
- [ ] Prideti anti-spam ir flood control nustatymus.
- [ ] Paruosti pranesimu centro ivykius: naujas atsakymas, mention, uzrakinta tema.
- [ ] Toliau isskaidyti daugiau forumo logikos is `bootstrap.php` i atskiras klases.
