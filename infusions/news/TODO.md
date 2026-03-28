# News TODO

## Paskirtis
- Naujienu modulis su kategorijomis, publikavimo eiga, komentaru integracija ir SEO.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.3`: pagrindinis sio modulio brandinimo etapas.
- `v1.4`: versijos, upgrade scenarijai ir galimos modulio klases.
- `v1.5`: SEO ir feed sluoksnio uzbaigimas.

## Svarbus failai
- `admin.php`: naujienu administravimas.
- `infusion.php`: modulio ikrovimas.
- `panel.php`: naujienu panele.
- `schema.php`, `upgrade.php`, `migrations/`: modulio schema ir atnaujinimai.
- `manifest.json`: modulio metaduomenys.

## Likusieji darbai

### Struktura ir publikavimas
- [ ] Prideti kategorijas.
- [ ] Prideti daugiau nei viena kategorija vienai naujienai, jei to reikes.
- [ ] Prideti `draft / review / scheduled / published / archived` statusus.
- [ ] Prideti aisku `juodrastis` darba su issaugojimu nepublikuojant.
- [ ] Prideti `scheduled publish`.
- [ ] Prideti publikavimo pabaigos data (`expire / archive after`).
- [ ] Prideti `sticky / featured / breaking news` busenas.
- [ ] Prideti seriju arba teminiu kolekciju logika.

### Redakcinis workflow
- [ ] Prideti `author profile integration`.
- [ ] Prideti redaktoriaus / patvirtintojo rodyma.
- [ ] Prideti perziuros (`preview`) ir juodrascio perziuros nuorodas.
- [ ] Prideti `WYSIWYG` integravima naujienos turinio redagavimui.
- [ ] Prideti redagavimo istorija ir revision palyginima.
- [ ] Prideti `change notes` redaguojant naujiena.
- [ ] Prideti galimybe reikalauti patvirtinimo pries publikavima.

### Turinys ir media
- [ ] Prideti aisku atskyrima tarp naujienos dalies (`excerpt / summary`) ir pilnos naujienos (`full body`).
- [ ] Prideti pilna naujienos turinio lauka, ne tik santrauka.
- [ ] Prideti `featured image`.
- [ ] Prideti paveiksliuku ikelima naujienai per admin forma.
- [ ] Prideti paveiksliuku rodymo taisykles sarasuose, pilname naujienos puslapyje ir panelese.
- [ ] Prideti YouTube nuorodu ikelima naujienai per admin forma.
- [ ] Prideti saugu YouTube embed rodyma naujienos perziuroje.
- [ ] Prideti galerija arba papildomus media failus.
- [ ] Prideti failu/priedu prisegima ten, kur tai prasminga.
- [ ] Prideti saltinio ir autoriaus nuorodas.
- [ ] Prideti skaitymo laiko (`reading time`) skaiciavima.

### Bendruomenes funkcijos
- [ ] Prideti komentarus.
- [ ] Prideti reakcijas / `like`.
- [ ] Prideti reitingavimus naujienoms.
- [ ] Prideti `bookmarks / favorites`.
- [ ] Prideti tagus.
- [ ] Prideti `related news`.
- [ ] Prideti perziuru skaiciavima ir `trending` logika.
- [ ] Prideti pranesimu centro ivykius po publikavimo.

### SEO ir platinimas
- [ ] Prideti SEO laukus kiekvienai naujienai.
- [ ] Prideti automatini `slug` generavima ir redirect, jei slug pasikeicia.
- [ ] Prideti `meta title`, `meta description`, `canonical`, `Open Graph`, `Twitter/X` laukus.
- [ ] Prideti `article schema` ir `breadcrumbs`.
- [ ] Prideti `RSS / Atom feed`.
- [ ] Prideti feed'us pagal kategorija ir taga.
- [ ] Prideti social share paveiksliuko logika arba OG image fallback.
- [ ] Prideti admin `SEO checklist` naujienos redagavimo lange.

### UX ir administravimas
- [ ] Prideti filtrus administravime pagal statusa, kategorija, autoriu ir data.
- [ ] Prideti greitus veiksmus: publish, unpublish, feature, archive.
- [ ] Prideti masines operacijas (`bulk publish`, `bulk archive`, `bulk delete`).
- [ ] Prideti nustatyma, kiek naujienu rodyti puslapyje.
- [ ] Prideti puslapiavima naujienu sarasui.
- [ ] Prideti naujienu paneles variantus: naujausios, populiariausios, `featured`, pagal kategorija.
- [ ] Prideti nustatyma, kiek naujienu rodyti kiekvienoje naujienu paneleje.
- [ ] Prideti locale tekstus modulio admin ir viesiems vaizdams.

### Architektura
- [ ] Jei reikes daugiau logikos, prideti `assets/` ir `classes/` struktura kaip kituose moduliuose.
- [ ] Apsvarstyti `NewsService`, `CategoryService`, `PublicationService`, `FeedService`, `SeoPresenter`, `MediaEmbedService` klases.
