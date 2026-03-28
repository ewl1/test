# Includes Functions TODO

## Paskirtis
- Vieta mazesniems helperiams ir pereinamajam sluoksniui tarp seno funkcinio kodo ir nauju paslaugu.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: tik locale, suderinamumo ir mazu helperiu tvarkymas.
- `v1.2`: komunikacijos ir profilio helperiu perziura.
- `v1.4`: helperiu mazinimas ten, kur atsiras servisai.

## Svarbus failai
- `posts.php`: irasu pagalbininkai.
- `shouts.php`: senasis shout helperis del suderinamumo.
- `pagination.php`: puslapiavimo logika.
- `output.php`: isvedimo helperiai.

## Likusieji darbai
- [ ] Nuspresti, kiek ilgai laikyti senus `posts/shouts` helperius suderinamumui.
- [ ] Sudeti daugiau locale raktu i senesnius helperius, jei jie dar naudojami.
- [ ] Palikti helperiuose tik tai, kas turi likti patogu vaizdui: `output`, `pagination`, paprasti formatteriai.
- [ ] Kai atsiras servisai, is helperiu perkelti:
- komentaru logika
- reakciju logika
- favorites / bookmarks logika
- report / moderation queue logika
- notification skaiciavimo logika
- [ ] Paruosti plona suderinamumo sluoksni, kad perejimas i klases nelauzytu senu kvietimu.
