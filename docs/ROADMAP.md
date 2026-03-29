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

## Horizontali dizaino ir UX kryptis

### Tikslas
- Padaryti MiniCMS vizualiai nuoseklu, lengvai skaitoma ir patogu naudoti tiek viesajame, tiek administracijos sluoksnyje.

### Prioritetai
- Sukurti bendra `design system` pagrinda: spalvu, tipografijos, tarpu, radius, shadow ir badge mygtuku taisykles.
- Sukurti `layout system` pagrinda:
- bendri konteineriai: `container`, `content`, `sidebar`, `full-width`
- pagrindiniai isdestymo sablonai: `home`, `content page`, `forum`, `news`, `profile`, `admin dashboard`, `admin table`, `admin form`
- vienodos taisykles, kada paneles krenta zemyn, kada turinys pereina i viena stulpeli ir kada meniu tampa mobiliu
- Suvienodinti korteliu, lenteliu, formu, tusciu busenu, klaidu, sekmes ir info pranesimu isvaizda.
- Sutvarkyti turinio tipografija ilgam skaitymui: forumui, naujienoms, komentarams, BBCode ir WYSIWYG turiniui.
- Pagerinti responsive elgsena: mobili navigacija, lankstesnes paneles, admin lenteles ir filtrai mazesniuose ekranuose.
- Pereiti prie `mobile-first` logikos: aiskus breakpoint'ai, `stacking` taisykles, lenteliu transformacija i korteles ir `off-canvas` filtrai mazuose ekranuose.
- Aiskiau suprojektuoti greitus veiksmus mobiliuose ekranuose: `sticky` veiksmu juostos, kompaktiski dropdown'ai, aiskesni CTA.
- Sustiprinti prieinamuma: kontrastas, `focus` busenos, klaviaturos navigacija, didesni klikabilus plotai.
- Sukurti aiskesne vieso puslapio vizualine hierarchija: hero blokai, paneliu presetai, CTA zonos, nariu meniu ir paieska.
- Forumo, naujienu ir shoutbox moduliams parengti savita, bet su bendra tema suderinama UI krypti.
- Sutvarkyti media pateikima: `featured image`, galeriju santykiai, avataru fallback'ai, YouTube ir paveiksliuku embed stiliai.
- Apibrezti ikonografijos strategija:
- pasirinkti viena pagrindine biblioteka (`Bootstrap Icons` arba `Font Awesome`)
- antrine biblioteka naudoti tik jei truksta konkreciu ikon
- `Entypo` naudoti tik jei tam atsiras labai aiski prieziastis
- tureti bendra ikon naudijimo zemelapi navigacijai, statusams ir veiksmams
- Atnaujinti admin UX: dashboard korteles, `panels` drag-drop griztamasis rysis, `infusions` sveikatos badge ir aiskesni veiksmai.
- Ilgainiui prideti temos personalizavimo krypti: `compact / wide` isdestymas, akcento spalvos, o veliau ir alternatyvios temos.

### Baigtumo kriterijai
- Viesi ir admin dalis naudoja ta pacia komponentu ir spalvu kalba.
- Kiekvienas pagrindinis modulis turi tvarkinga, lengvai skaitoma ir mobiliuose ekranuose nesubyrejancia isvaizda.
- Ikonos naudojamos nuosekliai, nepriklausomai nuo puslapio ar modulio.
- Pagrindiniai puslapiai remiasi aiskiais layout sablonais, o ne atsitiktiniais vienkartiniais isdestymais.
- Dizaino sprendimai atsispindi ne tik CSS faile, bet ir `TODO` planuose pagal tema, admin ir modulius.

## v1.1 Stabilumas, admin ir saugumo pagrindai

### Tikslas
- Uzbaigti tai, kas tiesiogiai veikia patikimuma: locale, kontrasta, sesijas, diagnostika ir administracijos darba.

### Prioritetai
- Uzbaigti likusiu admin ir paieskos puslapiu locale perkelima.
- Uzbaigti lietuvisku raidziu ir kontrasto audita admin zonoje.
- Prideti `session_regenerate_id()` po prisijungimo ir po admin prisijungimo.
- Jautriems admin veiksmams prideti papildomus patvirtinimus.
- Paruosti bendra `captcha` sluoksni svarbiausioms formoms: login, registracija, password reset, komentarai ir abuse/report formos.
- Apsibrezti `captcha` strategija: vietinis klausimas, honeypot, rate-limit eskalacija ir pasirenkamas isorinis provideris.
- Prideti `Clear cache / Clear rate limits / Clear reset tokens` irankius administracijoje.
- Diagnostikoje aiskiai suskirstyti pletinius i `butini`, `rekomenduojami`, `pasirenkami`.
- Jei po idiegimo rastas `install.php`, rodyti kritini admin-only ispejima dashboard ir diagnostikoje su rekomendacija nedelsiant pasalinti faila.
- Uzbaigti forumo ir shoutbox flood/spam pagrindus.
- Sutvarkyti centralizuota klaidu ir saugumo ivykiu registravima.
- Paruosti pirmuosius automatinius valymo darbus:
- `password reset cleanup`
- `rate limit cleanup`
- `session cleanup`
- laikinu failu ir pasibaigusiu tokenu valymas
- Pagerinti `administration/panels.php` ir `administration/infusions.php` darbo eiga: aiskesnes busenos, filtrai, sveikatos patikros ir patikimesni admin veiksmai.

### Baigtumo kriterijai
- Nebelieka akivaizdziu locale, kontrasto ar encoding problemu administracijoje.
- Prisijungimo ir admin sesiju srautai turi papildoma apsauga.
- `captcha` gali buti ijungiama pagal srauta ir nenaudojama aklai visur.
- Diagnostikos puslapis aiskiai paaiskina, ko projektui tikrai reikia.
- Admin puslapiai turi minimalius prieziuros ir saugumo irankius.

## v1.2 Komunikacija ir naudotoju paskyros

### Tikslas
- Padaryti MiniCMS ne tik administruojama sistema, bet ir gyva naudotoju platforma.

### Prioritetai
- Asmenines zinutes: `inbox`, `sent`, `archive`, neperskaitytu zinuciu skaicius.
- Naujos zinutes kurimas, pokalbio vaizdas ir atsakymo srautas.
- Flood control asmeninems zinutems ir galimybe blokuoti kita naudotoja.
- Soft delete zinutems, kad naudotojas galetu jas pasisalinti neprarasdamas audito.
- Abuse/report srautas privacioms zinutems ir moderatoriu perziura.
- Prisegtuku/medijos strategija asmeninems zinutems, jei tai bus leidziama ateityje.
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
- Dizaino kryptis naudotojo pusei:
- aiskus nario meniu ir paskyros centro isdestymas
- patogesnis `header` su paieska, pranesimais ir greitais veiksmais
- nuoseklus profilio, komentaru ir aktyvumo korteliu dizainas
- responsive paskyros vaizdas su patogiu mobiliu meniu ir `sticky` greitais veiksmais

### Baigtumo kriterijai
- Naudotojas turi pilna paskyros centra.
- Zinutems ir pranesimams yra aiskus UI, duomenu modelis ir moderavimo taisykles.
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
- automatinis `scheduled publish / unpublish / archive` per planuokli
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
- Moduliu UI polish:
- forumo temu saraso hierarchija, autoriaus blokas, badge ir embed vizualai
- naujienu korteles, hero blokai, autoriaus juosta ir media pateikimas
- shoutbox kompaktinis ir detalus rodymo rezimai, aiskesnes zinuciu grupes ir busenos
- daugiau semantiniu ikonu navigacijai, veiksmams, badge ir tuscioms busenoms

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
- `administration/infusions.php` jau vienodai rodo siuos tris laukus moduliu suvestineje.
- Infusion valdymo centras:
- priklausomybes ir konfliktai
- suderinamumas su MiniCMS / PHP / pletiniais
- `administration/infusions.php` jau rodo suderinamumo, priklausomybiu, konfliktu ir sveikatos santraukas kiekvienam moduliui.
- `administration/infusions.php` jau vienodai pateikia modulio `admin`, `settings`, `health` ir `upgrade` veiksmus, jei modulis juos deklaruoja.
- `ModuleSettingsContract` jau apibreztas SDK lygyje ir `developer mode` rodo, ar modulis deklaruoja sekcijas, formos schema ir validavimo taisykles.
- `ModuleDiagnosticsContract` jau apibreztas SDK lygyje ir `developer mode` rodo, ar modulis deklaruoja health checks, missing files, missing tables ir konfiguracijos busenas.
- `ModuleEventContract` jau apibreztas SDK lygyje ir `developer mode` rodo, ar modulis deklaruoja `notifications / activity feed` ivykius ir kokiais kanalais jie keliauja.
- `ModuleSearchContract` jau apibreztas SDK lygyje ir `developer mode` rodo, ar modulis deklaruoja paieskos saltinius, indeksuojamus laukus, permission filter ir svori.
- `ModulePresentationContract` jau apibreztas SDK lygyje ir `developer mode` rodo, ar modulis deklaruoja korteles badge, meta laukus, santraukas ir detalias sekcijas.
- moduliu ikonografijos taisykle jau sutarta: bendras pasirinkimas yra `Font Awesome 7`, o papildomas lokalus rinkinys leidziamas tik esant aiskiai produkto priezasciai ir tik modulio ribose.
- moduliu gyvenimo ciklo hook'ai jau dispatch'inami per `before/after install|upgrade|uninstall`, kartu su modulio-specifiniais `.<folder>` variantais.
- `developer mode` jau rodo `module_class`, registruotus runtime hook'us, migraciju sarasa, manifest laukus ir diagnostikos santrauka.
- `safe uninstall` taisykles jau apibreztos: admin puslapis tikrina priklausomus modulius, rodo paveikiamu irasu santrauka ir rizikingiems salinimams reikalauja papildomo `folder` patvirtinimo.
- `soft disable mode` taisykle jau sutarta: isjungtas modulis lieka idiegtas, bet neberegistruoja hook'u, neberodo paneliu, slepia viesas ir admin nuorodas, tiesioginius kreipinius uzdaro saugiu `404` arba `modulis isjungtas` atsaku ir netrina savo duomenu.
- moduliu galimybiu deklaravimo taisykle jau sutarta: teises skelbiamos per `manifest.permissions`, paneles per `manifest.provides.panels` ir realu `panel.php` arba klase, paieskos saltiniai per `ModuleSearchContract`, pranesimu ivykius per `ModuleEventContract`, o hook'ai i branduoli per `registerHooks()` ir pasirenkamai `manifest.hooks`.
- isplestas manifest standartas:
- `min_core_version`, `min_php_version`
- `required_extensions`
- `dependencies`, `conflicts`
- `provides`: paneles, teises, hook'ai, paieskos saltiniai
- `changelog`, `upgrade_notes`, `rollback_notes`
- bendros `install / upgrade / rollback` taisykles jau apibreztos SDK ir taikomos visiems naujiems moduliams
- ka modulis prideda: route'us, teises, paneles, hook'us
- sveikatos patikra ir `upgrade preview`
- aiskus badge rinkinys: `SDK`, `Legacy`, `Has migrations`, `Upgrade available`, `Missing manifest`
- modulio detales vaizdas: versijos, priklausomybes, teises, admin meniu, assets, hook'ai ir paskutiniai upgrade logai
- `safe uninstall` patikra: duomenu kiekis, priklausomybes, perspejimai ir patvirtinimo eiga
- `soft disable mode`, kad modulis galetu buti isjungtas nepaliekant sugedusio UI
- nustatymu ir diagnostikos kontraktai, kad modulis galetu deklaruoti savo `settings` ir `health` skiltis vienodu budu
- `developer mode`, rodantis `module_class`, migracijas, hook'us, manifest laukus ir diagnostikos informacija
- import / export kryptis modulio nustatymams ir konfiguracijai
- `seed / demo data` strategija: schema, pradinis seed ir demo duomenys turi buti aiskiai atskirti
- gyvenimo ciklo hook'ai: `before_install`, `after_install`, `before_upgrade`, `after_upgrade`, `before_uninstall`, `after_uninstall`
- Core update center:
- changelog
- checksum validacija
- backup before update
- rollback info
- Installer architektura:
- `includes/classes/MiniCMS/Installer/` turi likti branduolio diegimo sluoksniu
- moduliu DB schema, seed'ai ir upgrade logika turi likti `infusions/<modulis>/`
- core diegimas tik paruosta bazine sistema, po kurios moduliai diegiasi ar atsinaujina savo keliu
- `install.php` turi tureti aisku vedli su tabais / meniu:
- `Introduction`
- `System Requirements`
- `Database Settings`
- `Primary Admin Details`
- `Configure Core System`
- pakartotinai paleidus `install.php`, turi veikti `Recovery mode` su aiskiais veiksmais:
- `Cancel and Exit this Installer`
- `Change Primary Account Details`
- `Core System Installer`
- `Rebuild .htaccess`
- `Clean Installation`
- `Recovery mode` turi padeti atkurti diegima nepermaisant branduolio ir moduliu atsakomybiu
- Branduolio paslaugos ir registrai:
- system settings registry
- feature flags
- maintenance scheduler
- plugin conflict detector
- central error handler
- request logger
- task scheduler / cron registry
- pirmieji automatiniai darbai:
- `scheduled publish / unpublish / archive`
- `notification dispatch`
- `email queue sender`
- `audit / error log rotation`
- `temporary files cleanup`
- `cache warmup`
- `search reindex`
- `sitemap rebuild`
- `broken links check`
- `database backup before update`
- `forum maintenance`
- `news maintenance`
- `reputation / badges recalculation`
- `upload quarantine` ir karantino valymas
- search index abstraction
- search provider registry ir paieskos perindeksavimo sluoksnis
- content revision history
- draft / preview engine
- trash / recycle bin
- dependency graph viewer
- hook debugger
- OOP/PSR-4 kryptis:
- `App\\MiniCMS\\Auth\\AuthService`
- `App\\MiniCMS\\Mail\\Mailer`
- `App\\Security\\CaptchaService`
- `App\\Security\\CaptchaProviderInterface`
- `App\\Cache\\CacheStore`
- bazinis `Module SDK` moduliams:
- `InfusionManifest`, `InfusionContext`, `AbstractInfusionModule`, `HookRegistry`, `InfusionSdk`
- `tools/make-infusion-sdk.php` scaffold naujiems moduliams
- palaipsnis esamu moduliu perkelimas i SDK klases po viena
- `ModuleHealthResult`, `ModuleCompatibilityResult` ir `ModuleInspectorService` modulio analizei ir admin UI
- `ModuleLifecycleEvent`, `ModuleSettingsContract`, `ModuleDiagnosticsContract` vieningam modulio gyvenimo ciklui
- bendras `comments engine` klasiu rinkinys branduolyje
- bendras `notifications / activity feed` klasiu rinkinys branduolyje
- bendras `media/embed` klasiu rinkinys branduolyje
- modulio klasems likti savo `infusions/<modulis>/classes/`
- Temu ir UI architektura:
- isskirti bendrus UI helperius, view komponentu dalis ir `design token` saltinius
- palaipsniui mazinti atsitiktinius vienkartinius stilius puslapiuose
- apsvarstyti bendra ikon renderinimo helperi, kad viesoje ir admin dalyje ikonos butu kvieciamos vienodai

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
- automatinis `sitemap.xml` perstatymas po svarbiu turinio pakeitimu arba pagal grafika
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
- Dizaino kokybe ir prieinamumas:
- galutinis kontrasto ir prieinamumo auditas
- media lazy loading ir `responsive image` strategija
- vieningas ikonografijos ir iliustraciju stilius
- pilnas responsive auditas telefonu, plansetese ir mazesniuose laptop ekranuose

### Baigtumo kriterijai
- Projektas turi bazini SEO rinkini ir admin audita.
- Jei bus reikalinga GEO/AI kryptis, tam yra aiskus informacinis pagrindas.
- Svetaines greitis gerinamas ne vien OPcache, bet ir aplikacijos lygio optimizacijomis.

## Pastabos
- GEO darbai yra auksto prioriteto tik tada, jei projektas tures lokalaus ar faktinio turinio strategija.
- `php-class-diagram` verta jungti tada, kai branduolyje ir moduliuose atsiras daugiau realiu klasiu.
- Pilnas perrasymas i OOP nera tikslas; tikslas yra nuoseklus sluoksniu isgryninimas.

## Artimiausi UI prioritetai
- Praktinis pirmu darbu sarasas pateiktas `docs/UI-TOP-10.md`.
- Sis sarasas turi padeti pasirinkti, ka realiai verta pradeti daryti pirmiausia.

## Rekomenduojama vykdymo seka
1. Uzbaigti `v1.1`.
2. Eiti i `v1.2`, kad naudotojo verte butu matoma is karto.
3. Tada brandinti `v1.3` modulius.
4. Po to sutvarkyti `v1.4` atnaujinimu ir branduolio platforma.
5. Galiausiai plesti `v1.5` SEO, GEO ir optimizacijos sluoksni.
