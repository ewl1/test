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
- Tik bendri svetaines vaizdai keliauja i `/images/`.

## Likusieji darbai
- [ ] Ta pacia `assets/locale/classes` struktura pritaikyti visiems aktyviems moduliams.
- [ ] Sukurti vieninga manifest lokalizavimo strategija.
- [ ] Apibrezti bendra moduliu UI sutarti:
- korteles, lenteles, tuscios busenos, info/klaidu pranesimai
- admin veiksmu mygtukai ir sveikatos badge
- viesu vaizdu antrastes, meta juostos ir turinio tipografija
- ikonografijos taisykles moduliams: navigacija, statusai, veiksmai ir media tipai
- layout integracijos taisykles moduliams: kaip modulis isikabina i `content`, `sidebar`, `full-width` ir paneliu zonas
- [ ] Susitarti, kaip moduliai naudos bendrus temos `design token` ir kada jiems leidziami nuosavi CSS variantai.
- [ ] Susitarti, kaip moduliai naudos bendra ikon biblioteka ir kada galima papildoma rinkini naudoti lokaliai.
- [ ] Vienodai rodyti `installed version`, `manifest version` ir `available upgrade`.
- [ ] Apibrezti bendras `install / upgrade / rollback` taisykles visiems moduliams.
- [ ] Isplesti manifest standarta papildomais laukais:
- `min_core_version`
- `min_php_version`
- `required_extensions`
- `dependencies`
- `conflicts`
- `provides`: paneles, route'ai, teises, hook'ai, paieskos saltiniai
- `changelog`
- `upgrade_notes`
- `rollback_notes`
- [ ] Padaryti, kad `administration/infusions.php` moketu rodyti suderinamuma, priklausomybes, konfliktus ir modulio sveikatos busena.
- [ ] Vienodai pateikti modulio `admin`, `settings`, `health`, `upgrade` veiksmus, jei modulis juos turi.
- [ ] Apibrezti, kaip moduliai skelbia:
- savo route'us
- savo teises
- savo paneles
- paieskos saltinius
- pranesimu ivykius
- hook'us i branduoli
- [ ] Vienodai apibrezti, kaip modulis skelbia `notifications` ir `activity feed` ivykius:
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
- [ ] Vienodai apibrezti, kaip modulis pateikia paieskos metaduomenis:
- indeksuojami laukai
- rezultato URL
- rezultato pavadinimas ir santrauka
- kategorija / tipas
- leidimu filtras
- svoris/relevancija
