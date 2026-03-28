# MiniCMS Roadmap

## Paskirtis
- Sis dokumentas apibriezia artimiausius MiniCMS etapus ir produkto krypti.
- Katalogu `TODO.md` failai lieka detalus darbiniu uzduociu sarasai.
- Jei uzduotis nebetelpa i esama etapa, ji pirmiausia turi atsirasti cia, o tik po to katalogo `TODO.md`.

## Kaip naudoti
- `v1.1` skirtas stabilumui, admin kokybei ir saugumo pamatams.
- `v1.2` skirtas komunikacijai, profiliams ir naudotoju patirciai.
- `v1.3` skirtas moduliu brandinimui: forumui, naujienoms, komentarams ir reakcijoms.
- `v1.4` skirtas branduolio paslaugoms, versijoms ir atnaujinimu platformai.
- `v1.5` skirtas SEO, GEO/AI-friendly turiniui ir optimizacijai.

## v1.1 Stabilumas, admin ir saugumo pagrindai

### Tikslas
- Uzbaigti tai, kas tiesiogiai veikia patikimuma: locale, kontrasta, sesijas, diagnostika ir administracijos darba.

### Prioritetai
- Uzbaigti likusiu admin ir paieskos puslapiu locale perkelima.
- Uzbaigti lietuvisku raidziu ir kontrasto audita admin zonoje.
- Prideti `session_regenerate_id()` po prisijungimo ir po admin prisijungimo.
- Jautriems admin veiksmams prideti papildomus patvirtinimus.
- Prideti `Clear cache / Clear rate limits / Clear reset tokens` irankius administracijoje.
- Diagnostikoje aiskiai suskirstyti pletinius i `butini`, `rekomenduojami`, `pasirenkami`.
- Uzbaigti forumo ir shoutbox flood/spam pagrindus.
- Sutvarkyti centralizuota klaidu ir saugumo ivykiu registravima.

### Baigtumo kriterijai
- Nebelieka akivaizdziu locale, kontrasto ar encoding problemu administracijoje.
- Prisijungimo ir admin sesiju srautai turi papildoma apsauga.
- Diagnostikos puslapis aiskiai paaiskina, ko projektui tikrai reikia.
- Admin puslapiai turi minimalius prieziuros ir saugumo irankius.

## v1.2 Komunikacija ir naudotoju paskyros

### Tikslas
- Padaryti MiniCMS ne tik administruojama sistema, bet ir gyva naudotoju platforma.

### Prioritetai
- Asmenines zinutes: `inbox`, `sent`, `archive`, neperskaitytu zinuciu skaicius.
- Flood control asmeninems zinutems ir galimybe blokuoti kita naudotoja.
- Soft delete zinutems, kad naudotojas galetu jas pasisalinti neprarasdamas audito.
- Pranesimu centras:
- nauja asmenine zinute
- naujas atsakymas forume
- naujas komentaro atsakymas ar naujas komentras
- sistemos pranesimai
- `profile.php` perrasymas:
- profilis, avataras, kontaktai, parasas
- privatumo nustatymai
- activity feed
- user statistics
- user groups / roles rodymas
- security skirtukas: slaptazodis, sesijos, 2FA

### Baigtumo kriterijai
- Naudotojas turi pilna paskyros centra.
- Zinutems ir pranesimams yra aiskus UI ir duomenu modelis.
- Profilis tampa vienu is pagrindiniu bendruomenes tasku.

## v1.3 Moduliai ir bendruomenes funkcijos

### Tikslas
- Isauginti esamus modulius iki pilnesnio bendruomenes CMS lygio.

### Prioritetai
- Forumo infusion:
- kategorijos, subforumai, temos, atsakymai
- pinned / locked temos
- moderation log
- paieska
- unread tracking
- attachments
- mention sistema
- like / reaction sistema
- anti-spam ir flood control
- Naujienu infusion:
- kategorijos
- `draft / published`
- scheduled publish
- author profile integration
- komentarai
- tagai
- related news
- featured image
- SEO laukai kiekvienai naujienai
- RSS / Atom feed
- Bendri bendruomenes varikliai:
- comments engine
- reactions
- bookmarks / favorites
- reporting system
- moderation queue
- user reputation
- badges / achievements
- polls

### Baigtumo kriterijai
- Forumas ir naujienos turi ne tik bazini CRUD, bet ir bendruomenes funkcijas.
- Reakcijos, komentarai ir report sistema gali buti pakartotinai naudojami keliuose moduliuose.
- Moderatoriai turi aisku darba su queue ir audit logika.

## v1.4 Branduolys, versijos ir atnaujinimai

### Tikslas
- Paruosti technini pagrinda ilgesniam produkto gyvavimui ir saugesniems atnaujinimams.

### Prioritetai
- MiniCMS versijos rodymas admin footer ir diagnostics puslapyje.
- Update channel: `stable / beta`.
- Infusion versiju rodymas:
- installed version
- manifest version
- available upgrade
- Core update center:
- changelog
- checksum validacija
- backup before update
- rollback info
- Branduolio paslaugos ir registrai:
- system settings registry
- feature flags
- maintenance scheduler
- plugin conflict detector
- central error handler
- request logger
- task scheduler / cron registry
- search index abstraction
- content revision history
- draft / preview engine
- trash / recycle bin
- dependency graph viewer
- hook debugger
- OOP/PSR-4 kryptis:
- `App\\Auth\\AuthService`
- `App\\Mail\\Mailer`
- `App\\Cache\\CacheStore`
- modulio klasems likti savo `infusions/<modulis>/classes/`

### Baigtumo kriterijai
- Versijos ir atnaujinimai turi aisku admin centra.
- Branduolys turi svarbiausias paslaugas ir registrus.
- Naujos funkcijos nebesiplecia tik per didelius helperius.

## v1.5 SEO, GEO/AI turinys ir optimizacija

### Tikslas
- Pagerinti matomuma paieskoje, paruosima AI skaitymui ir bendra svetaines greiti.

### Prioritetai
- SEO:
- `meta title / description` kiekvienam puslapiui
- Open Graph
- Twitter/X cards
- canonical URL
- breadcrumbs schema
- article schema
- organization schema
- `sitemap.xml`
- `robots.txt`
- SEO audit puslapis admin dalyje
- redirect manager `301/302`
- broken links checker
- GEO / AI-friendly kryptis:
- aiskus turinio blokai ir antrastes
- FAQ struktura
- schema markup
- author / source signalai
- aiskus faktiniai puslapiai
- citation-friendly tekstas
- `llms.txt` ar panasi AI-friendly dokumentacija
- knowledge pages apie svetaine, projekta ir autorius
- Optimizavimas:
- page cache
- panel cache
- query profiling
- asset bundling / minify
- image optimization
- lazy loading
- cache warmup

### Baigtumo kriterijai
- Projektas turi bazini SEO rinkini ir admin audita.
- Jei bus reikalinga GEO/AI kryptis, tam yra aiskus informacinis pagrindas.
- Svetaines greitis gerinamas ne vien OPcache, bet ir aplikacijos lygio optimizacijomis.

## Pastabos
- GEO darbai yra auksto prioriteto tik tada, jei projektas tures lokalaus ar faktinio turinio strategija.
- `php-class-diagram` verta jungti tada, kai branduolyje ir moduliuose atsiras daugiau realiu klasiu.
- Pilnas perrasymas i OOP nera tikslas; tikslas yra nuoseklus sluoksniu isgryninimas.

## Rekomenduojama vykdymo seka
1. Uzbaigti `v1.1`.
2. Eiti i `v1.2`, kad naudotojo verte butu matoma is karto.
3. Tada brandinti `v1.3` modulius.
4. Po to sutvarkyti `v1.4` atnaujinimu ir branduolio platforma.
5. Galiausiai plesti `v1.5` SEO, GEO ir optimizacijos sluoksni.
