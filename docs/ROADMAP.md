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
- Pagerinti `administration/panels.php` ir `administration/infusions.php` darbo eiga: aiskesnes busenos, filtrai, sveikatos patikros ir patikimesni admin veiksmai.

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
- bendras `notifications` variklis su tipais, nuostatomis ir pristatymo kanalais
- bendras `activity feed` variklis su actor/action/target modeliu
- `profile.php` perrasymas:
- profilis, avataras, kontaktai, parasas
- privatumo nustatymai
- activity feed
- user statistics
- user groups / roles rodymas
- security skirtukas: slaptazodis, sesijos, 2FA
- Paneles kaip naudotojo patirties dalis:
- matomumas pagal role ir puslapi
- lankstesni `member panel` scenarijai
- atskiros paneles pranesimams, aktyvumui ir statistikoms

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
- paveiksliuku ikelimas ir rodymas
- YouTube nuorodu ikelimas ir embed rodymas
- mention sistema
- like / reaction sistema
- anti-spam ir flood control
- per-forum teises ir `staff only` forumai
- forumu kurimas ir matomumas pagal roles
- `move / split / merge`, soft delete ir restore
- `solved / accepted answer`
- temu prefiksai, tagai, prenumeratos ir favorites
- draft / auto-save, quick reply, multi-quote
- moderatoriu vidines pastabos ir aktyvumo/statistikos sluoksnis
- rango / statuso rodymas, badge'ai ir `thanks` mygtukai
- IP rodymas moderatoriams ir redagavimo priezastys moderavimo veiksmams
- Naujienu infusion:
- kategorijos
- `draft / review / scheduled / published / archived`
- aiskus `juodrastis` workflow
- scheduled publish
- publish pabaigos data ir archyvavimas
- `sticky / featured / breaking news`
- author profile integration
- redaktoriaus / patvirtintojo rodymai
- `WYSIWYG` integracija redagavimui
- dalis naujienos (`excerpt`) ir pilna naujiena (`full body`)
- komentarai
- reitingavimai
- reakcijos, favorites ir perziuru skaiciavimas
- tagai
- related news
- featured image
- paveiksliuku ikelimas ir rodymas
- YouTube nuorodu ikelimas ir embed rodymas
- galerija / media / priedai
- saltiniai, autoriaus nuorodos ir `reading time`
- SEO laukai kiekvienai naujienai
- `slug` ir redirect logika po pakeitimu
- `article schema`, OG/Twitter ir social share paveiksliukas
- RSS / Atom feed
- feed'ai pagal kategorijas ir tagus
- kiek naujienu rodyti, puslapiavimas ir paneliu limitai
- redagavimo istorija, preview ir approval workflow
- Bendri bendruomenes varikliai:
- notifications / activity feed:
- bendri ivykiu tipai moduliams
- `in-app` ir `email` pristatymas
- activity feed filtrai ir matomumo taisykles
- moduliu ivykiu publikavimas i notification centra ir feed'a
- paieskos variklis:
- vieningi paieskos saltiniai visiems moduliams
- rezultatu skaidymas pagal tipa ir sekcija
- filtrai pagal data, autoriu, kategorija, taga ir moduli
- relevancijos ir svoriu modelis
- teisiu filtras, kad paieska gerbtu privataus turinio ribas
- `autocomplete`, `highlight` ir paieskos analitika
- `reindex` ir paieskos diagnostika admin dalyje
- comments engine
- vieningas komentaru taikinio modelis skirtingiems moduliams
- `flat` ir `threaded` scenarijai pagal poreiki
- moderavimo busenos ir `soft delete / restore`
- `quote`, `mention`, `reaction`, `report`
- komentaru puslapiavimas, rusiavimas ir limitai
- prenumeratos ir pranesimu centro ivykiu sluoksnis
- komentaru `policy` sluoksnis: ar modulis leidzia komentarus, kokias teises ir koki renderi naudoja
- reactions
- bookmarks / favorites
- reporting system
- moderation queue
- user reputation
- badges / achievements
- polls
- Paneliu sistema:
- paneliu presetai puslapiams
- paneliu `duplicate`, `preview`, `restore defaults`
- paneliu cache ir rodymo taisykles

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
- Infusion valdymo centras:
- priklausomybes ir konfliktai
- suderinamumas su MiniCMS / PHP / pletiniais
- ka modulis prideda: route'us, teises, paneles, hook'us
- sveikatos patikra ir `upgrade preview`
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
- search provider registry ir paieskos perindeksavimo sluoksnis
- content revision history
- draft / preview engine
- trash / recycle bin
- dependency graph viewer
- hook debugger
- OOP/PSR-4 kryptis:
- `App\\Auth\\AuthService`
- `App\\Mail\\Mailer`
- `App\\Cache\\CacheStore`
- bendras `comments engine` klasiu rinkinys branduolyje
- bendras `notifications / activity feed` klasiu rinkinys branduolyje
- bendras `media/embed` klasiu rinkinys branduolyje
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
- forumui:
- canonical temos ir puslapiavimo URL
- breadcrumbs schema
- strukturuoti duomenys diskusijoms
- naujienoms:
- `article schema`
- OG/Twitter korteles
- canonical ir redirect logika keiciant slug
- feed'ai pagal kategorija ir taga
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
- paieskos uzklausu profiliavimas ir paieskos cache
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
