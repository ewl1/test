# Atsisiuntimai

Sis modulis sugeneruotas per MiniCMS Module SDK scaffold.

## Failai
- `manifest.json`: modulio metaduomenys ir registracija
- `classes/`: modulio SDK klase
- `support/`: proceduriniai helperiai pereinamajam laikotarpiui
- `panel.php`: paneles turinys ir legacy `openside()/closeside()` apvalkalas
- `admin.php`: admin vaizdas
- `schema.php`: diegimo DB schema
- `upgrade.php`: legacy fallback atnaujinimo failas
- `migrations/`: versijiniai atnaujinimu ir rollback zingsniai
- `uninstall.php`: pasalinimo logika
- `locale/`: modulio tekstai
- `assets/`: modulio CSS ir JS

## Manifest lokalizacija
- Manifest lokalizuojami tekstai turi naudoti bendra `locale_prefix`, pvz. `downloads.manifest`.
- Branduolys pagal si prefiksa automatikai iesko:
  - `downloads.manifest.name`
  - `downloads.manifest.description`
  - `downloads.manifest.default_panel_name`
  - `downloads.manifest.permissions.<slug>.name`
  - `downloads.manifest.permissions.<slug>.description`
  - `downloads.manifest.admin_menu.<slug>.title`
- Jei reikia tikslesnes kontroles, manifest gali naudoti explicit `name_key`, `description_key`, `title_key`, `notes_keys`, `upgrade_notes_keys`, `rollback_notes_keys`.

## Settings contract
- Jei modulis turi tikra nustatymu puslapi, rekomenduojama klasei papildomai igyvendinti `App\MiniCMS\Infusions\ModuleSettingsContract`.
- Ta sutartis leidzia vienodai deklaruoti:
  - nustatymu sekcijas
  - formos schema
  - validavimo taisykles
- `administration/infusions.php` developer mode tada matys, ar modulis si kontrakta igyvendina.

## Diagnostics contract
- Jei modulis turi savo health check ar diagnostikos puslapi, rekomenduojama klasei papildomai igyvendinti `App\MiniCMS\Infusions\ModuleDiagnosticsContract`.
- Ta sutartis leidzia vienodai deklaruoti:
  - health checks
  - missing files
  - missing tables
  - konfiguracijos busenas
- `administration/infusions.php` developer mode tada matys, ar modulis si kontrakta igyvendina.

## Events contract
- Jei modulis skelbia ivykius i notification centra ar activity feed, rekomenduojama klasei papildomai igyvendinti `App\MiniCMS\Infusions\ModuleEventContract`.
- Ta sutartis leidzia vienodai deklaruoti:
  - ivykio tipa
  - pavadinima ir santrauka
  - actor / target duomenis
  - matomumo taisykles
  - kanalus: `notifications`, `activity_feed` arba abu
- `administration/infusions.php` developer mode tada matys, ar modulis si kontrakta igyvendina.

## Search contract
- Jei modulis teikia paieskos saltinius, rekomenduojama klasei papildomai igyvendinti `App\MiniCMS\Infusions\ModuleSearchContract`.
- Ta sutartis leidzia vienodai deklaruoti:
  - indeksuojamus laukus
  - rezultato URL
  - rezultato pavadinima ir santrauka
  - kategorija / tipa
  - leidimu filtra
  - svori / relevancija
- `administration/infusions.php` developer mode tada matys, ar modulis si kontrakta igyvendina.

## Presentation contract
- Jei modulis nori vienodai deklaruoti, ka rodyti savo korteleje ir ka detaliame rodinyje, rekomenduojama klasei papildomai igyvendinti `App\MiniCMS\Infusions\ModulePresentationContract`.
- Ta sutartis leidzia vienodai deklaruoti:
  - korteles badge
  - korteles meta laukus
  - korteles santraukas
  - detalaus rodinio sekcijas
- Core rezervuoja badge raktus:
  - `sdk`
  - `legacy`
  - `has_migrations`
  - `upgrade_available`
  - `missing_manifest`
- `administration/infusions.php` developer mode tada matys, ar modulis si kontrakta igyvendina.

## Standartas
- `bootstrap.php`, `admin.php` ir `panel.php` turi likti ploni entrypoint failai.
- Jei modulyje laikinai dar reikia proceduriniu helperiu, jie keliauja i `support/` ir skaidomi pagal atsakomybe.
- Jei logika tampa pakartotinai naudojamu servisu ar presenteriu, ji keliama i `classes/`.
- Nedarykite vieno monolitinio `feature_pack.php` tipo failo, jei helperius galima isskaidyti i aiskius `support/` failus.

## Migrations
- Core automatikai uzdeda lock per install / upgrade / uninstall, todel du adminai negali paleisti to paties proceso vienu metu.
- `administration/infusions.php` rodo aktyvu migraciju lock, paskutinius zingsnius ir rollback istorija.
- Rekomenduojamas failu formatas:
  - `migrations/001_1.0.1.php`
  - `migrations/001_1.0.1.rollback.php`
- Jei `migrations/` neturi vykdomu zingsniu, galima naudoti `upgrade.php` kaip fallback mechanizma.

## Lifecycle taisykles
- Install turi kurti tik modulio schema ir pradinius techninius duomenis.
- Demo turinys turi buti atskirtas nuo bazinio seed.
- Upgrade pirmiausia naudoja `migrations/`, o `upgrade.php` yra tik legacy fallback.
- Rollback vykdomas tik jau paleistiems zingsniams atbuline tvarka ir remiasi `.rollback.php` failais.
- Jei moduliui reikia rankinio veiksmo, tai turi buti aprasyta `upgrade_notes` arba `rollback_notes`.

## Safe uninstall
- Core pries uninstall tikrina, ar modulis neturi priklausomu idiegtu moduliu.
- Admin UI turi parodyti, kiek core ir modulio irasu bus paliesta.
- Jei uninstall paliecia duomenis, turi buti papildomas patvirtinimas, pvz. `folder` ivestis.

## Lifecycle hook'ai
- Branduolys automatikai dispatch'ina:
  - `before_install`, `after_install`
  - `before_upgrade`, `after_upgrade`
  - `before_uninstall`, `after_uninstall`
- Taip pat yra modulio-specifiniai variantai su `.<folder>` gale.
- Hook listener'iai gali buti registruojami per `registerHooks()` ir `infusion_add_hook()`.