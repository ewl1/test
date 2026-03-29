# Infusions TODO

## Paskirtis
- Bendra moduliu architektura, asset ir locale taisykles, versijos, hook'ai ir modulio standartai.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: suvienodinti locale ir asset struktura esamiems moduliams.
- `v1.3`: brandinti forumo, naujienu ir shoutbox funkcijas.
- `v1.4`: versijos, upgrade kelias, modulio hook'ai ir atnaujinimai.

## Taisykles
- Modulio tekstai laikomi `infusions/<modulis>/locale/`.
- Modulio CSS ir JS laikomi `infusions/<modulis>/assets/`.
- Jei modulis turi savo klases, jos laikomos `infusions/<modulis>/classes/`.
- Jei modulis dar turi legacy procedurine logika, ji laikoma `infusions/<modulis>/support/` ir skaidoma pagal atsakomybe.
- Modulio schema, seed'ai ir upgrade failai laikomi paciame `infusions/<modulis>/` kataloge.
- Tik bendri svetaines vaizdai keliauja i `/images/`.

## Likusieji darbai
- [x] Prideti pradine `Module SDK` baze su manifest parseriu, context objektu, hook registry ir scaffold generatoriumi.
- [x] Leisti moduliams nurodyti `module_class` ir palaipsniui pereiti nuo vien failu prie klasiu.
- [ ] Ta pacia `assets/locale/classes` struktura pritaikyti visiems aktyviems moduliams.
- [ ] Ta pacia `support/` skaidymo taisykle pritaikyti visiems legacy moduliams, kad neliktu monolitiniu `feature_pack` tipo failu.
- [ ] Sukurti vieninga manifest lokalizavimo strategija.
- [ ] Apibrezti bendra moduliu UI sutarti:
- korteles, lenteles, tuscios busenos, info/klaidu pranesimai
- admin veiksmu mygtukai ir sveikatos badge
- viesu vaizdu antrastes, meta juostos ir turinio tipografija
- ikonografijos taisykles moduliams: navigacija, statusai, veiksmai ir media tipai
- layout integracijos taisykles moduliams: kaip modulis isikabina i `content`, `sidebar`, `full-width` ir paneliu zonas
- [ ] Susitarti, kaip moduliai naudos bendrus temos `design token` ir kada jiems leidziami nuosavi CSS variantai.
- [ ] Susitarti, kaip moduliai naudos bendra ikon biblioteka ir kada galima papildoma rinkini naudoti lokaliai.
- [x] Vienodai rodyti `installed version`, `manifest version` ir `available upgrade`.
- [x] Apibrezti bendras `install / upgrade / rollback` taisykles visiems moduliams.
- [ ] Sutarti, kad core Installer iraso tik branduolio DB, o moduliai savo lenteles ir nustatymus susikuria per savo install/upgrade mechanizma.
- [ ] Po SDK branduolio pradeti perkelti esamus modulius i klases po viena, pradedant nuo `news`, tada `shoutbox`, o po to `forum`.
- [x] Isplesti manifest standarta papildomais laukais:
- `min_core_version`
- `min_php_version`
- `required_extensions`
- `dependencies`
- `conflicts`
- `provides`: paneles, teises, hook'ai, paieskos saltiniai
- `changelog`
- `upgrade_notes`
- `rollback_notes`
- [x] Padaryti, kad `administration/infusions.php` moketu rodyti suderinamuma, priklausomybes, konfliktus ir modulio sveikatos busena.
- [x] Vienodai pateikti modulio `admin`, `settings`, `health`, `upgrade` veiksmus, jei modulis juos turi.
- [x] Apibrezti `ModuleSettingsContract`, kad modulis vienodai deklaruotu savo nustatymu forma, sekcijas ir validavimo taisykles.
- [x] Apibrezti `ModuleDiagnosticsContract`, kad modulis galetu grazinti savo health check, missing files, missing tables ir konfiguracijos busenas.
- [x] Apibrezti moduliu gyvenimo ciklo hook'us:
- `before_install`, `after_install`
- `before_upgrade`, `after_upgrade`
- `before_uninstall`, `after_uninstall`
- [ ] Susitarti del `seed / demo data` strategijos:
- schema, pradinis seed ir demo turinys turi buti atskirti
- modulis turi galeti isidiegti be demo duomenu
- [ ] Apibrezti `soft disable mode`, kad isjungtas modulis nepaliktu sugedusiu paneliu, route'u ar admin nuorodu.
- [ ] Apibrezti `safe uninstall` taisykles:
- patikrinti ar yra priklausomu moduliu
- parodyti kiek duomenu bus paliesta
- reikalauti papildomo patvirtinimo rizikingiems salinimams
- [ ] Apibrezti moduliu `import / export` krypti nustatymams, seed'ams ar lokaliems presetams.
- [x] Apibrezti `developer mode`, kuriame modulis rodo:
- `module_class`
- registruotus hook'us
- migraciju sarasa
- manifest laukus ir diagnostikos santrauka
- [ ] Apibrezti, kaip moduliai skelbia:
- savo route'us
- savo teises
- savo paneles
- paieskos saltinius
- pranesimu ivykius
- hook'us i branduoli
- [x] Vienodai apibrezti, kaip modulis skelbia `notifications` ir `activity feed` ivykius:
- ivykio tipas
- ivykio pavadinimas ir santrauka
- target/actor duomenys
- matomumo taisykles
- ar ivyki reikia siusti i notification centra, activity feed ar abu
- [ ] Vienodai apibrezti, kaip modulis pateikia media/embed taisykles:
- ar leidziami vietiniai paveiksliukai
- ar leidziami YouTube embed'ai
- kokie limitai ir validacijos taikomi
- [ ] Vienodai apibrezti, kaip modulis pateikia vizualines busenas:
- `featured`, `new`, `locked`, `disabled`, `warning`, `needs update`
- kokie badge ir meta laukeliai naudojami viesame ir admin rodinyje
- kokios ikonos siejamos su tomis busenomis
- [x] Vienodai apibrezti, kaip modulis pateikia paieskos metaduomenis:
- indeksuojami laukai
- rezultato URL
- rezultato pavadinimas ir santrauka
- kategorija / tipas
- leidimu filtras
- svoris/relevancija
- [x] Apibrezti moduliu badge ir detalios perziuros sutarti:
- `SDK`, `Legacy`, `Has migrations`, `Upgrade available`, `Missing manifest`
- ka modulis rodo korteleje, o ka detaliame rodinyje
