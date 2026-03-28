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

### Struktura ir navigacija
- [ ] Prideti temoms ir atsakymams paieskos filtrus paciame forume.
- [ ] Prideti temu prefiksus ir tagus.
- [ ] Prideti `jump to unread`, paskutinio skaitymo zymes ir aiskesni `last read` zymekli.
- [ ] Prideti forumo ir temu prenumeratas (`watch forum`, `watch topic`).
- [ ] Prideti forumo bookmark/favorite temas naudotojui.
- [ ] Prideti drafto / auto-save logika kuriant temas ir atsakymus.
- [ ] Prideti forumo kurima ir prieigos taisykles pagal roles.

### Moderavimas
- [ ] Prideti moderavimo istorija ir `redagavo` informacija.
- [ ] Prideti `move / split / merge` temu veiksmus.
- [ ] Prideti soft delete ir atstatyma temoms bei atsakymams.
- [ ] Prideti moderatoriaus vidines pastabas temai ar naudotojui.
- [ ] Prideti per-forum teises ir galimybe tureti `staff only` forumus.
- [ ] Prideti report/mygtukus ir jungti su bendra `moderation queue`.
- [ ] Rodyti IP adresa moderatoriams ir administratoriams, bet ne paprastiems nariams.
- [ ] Prideti redagavimo priezasties lauka moderavimo veiksmams ir atsakymu redagavimui.
- [ ] Rodyti `redaguota del priezasties` informacija ir saugoti ja moderavimo istorijoje.

### Bendruomenes funkcijos
- [ ] Prideti `unread tracking`.
- [ ] Prideti priedus (`attachments`).
- [ ] Prideti paveiksliuku ikelima i temos ir atsakymo forma.
- [ ] Prideti saugu paveiksliuku rodyma temuose ir atsakymuose.
- [ ] Prideti YouTube nuorodu ikelima ir automatini embed rodyma.
- [ ] Prideti `mention` sistema.
- [ ] Prideti `like / reaction` sistema.
- [ ] Prideti forumo rango / statuso rodyma prie nario vardo ir avataro.
- [ ] Prideti badge'u rodyma prie posto arba profilio korteles.
- [ ] Prideti `padeka / thanks` mygtuka prie atsakymu.
- [ ] Prideti `solved / accepted answer` temoms, kur tai prasminga.
- [ ] Prideti apklausas (`polls`) prie temu.
- [ ] Prideti naudotojo forumo statistika: sukurtos temos, atsakymai, paskutinis aktyvumas.

### Anti-spam ir patikimumas
- [ ] Prideti anti-spam ir flood control nustatymus.
- [ ] Prideti `slow mode` atskiriems forumams ar temoms.
- [ ] Prideti failu priedu limitus, MIME tikrinima ir kvotas.
- [ ] Prideti atskiras taisykles paveiksliukams ir YouTube nuorodoms: limitai, validacija, leidziami domenai.
- [ ] Paruosti pranesimu centro ivykius: naujas atsakymas, mention, uzrakinta tema, `solved` busena.

### UX ir SEO
- [ ] Prideti `multi-quote` ir greito atsakymo forma temos apacioje.
- [ ] Prideti kanoninius forumo/temos URL ir puslapiavimo canonical logika.
- [ ] Prideti `breadcrumbs` ir strukturuotus duomenis forumo temoms.
- [ ] Prideti nauju ir aktyviu temu paneles bei `latest forum activity` blokus.
- [ ] Sutvarkyti temu saraso vizualine hierarchija: `pinned`, `locked`, `solved`, `new` badge ir neperskaitytu temu zymekliai.
- [ ] Paruosti autoriaus bloka prie posto: avataras, role, rangas, badge'ai, statistika ir `thanks`.
- [ ] Paruosti aisku media rodymo stiliu paveiksliukams, priedams ir YouTube embed'ams.
- [ ] Apsvarstyti kompaktini ir detalesni temu saraso rodymo rezima.

### Architektura
- [ ] Toliau isskaidyti daugiau forumo logikos is `bootstrap.php` i atskiras klases.
