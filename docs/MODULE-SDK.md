# Module SDK

## Paskirtis
- `MiniCMS Module SDK` leidzia kurti infusion modulius vienodu principu.
- SDK nepanaikina seno `admin.php / panel.php / schema.php / upgrade.php` modelio, o ji apgaubia ir leidzia pereiti prie klasiu palaipsniui.

## Branduolio dalys
- `App\MiniCMS\Infusions\InfusionManifest`
- `App\MiniCMS\Infusions\InfusionContext`
- `App\MiniCMS\Infusions\InfusionModuleInterface`
- `App\MiniCMS\Infusions\AbstractInfusionModule`
- `App\MiniCMS\Infusions\ModuleSettingsContract`
- `App\MiniCMS\Infusions\ModuleDiagnosticsContract`
- `App\MiniCMS\Infusions\ModuleEventContract`
- `App\MiniCMS\Infusions\ModuleSearchContract`
- `App\MiniCMS\Infusions\ModulePresentationContract`
- `App\MiniCMS\Infusions\SimplePanelModule`
- `App\MiniCMS\Infusions\HookRegistry`
- `App\MiniCMS\Infusions\InfusionSdk`
- `App\MiniCMS\Infusions\ModuleScaffolder`

## Kas nauja dabartiniame modelyje
- `migrations/` yra pirmaeilis modulio atnaujinimu kelias.
- `upgrade.php` lieka kaip legacy fallback, jei nera vykdytinu versioned migration failu.
- Core automatikai uzdeda DB lock per `install / upgrade / uninstall`.
- `administration/infusions.php` rodo aktyvu migraciju lock, paskutinius zingsnius ir rollback istorija.
- `bootstrap.php`, `admin.php` ir `panel.php` turi likti ploni, o proceduriniai helperiai turi keliauti i `support/`.
- `ModuleSettingsContract` leidzia moduliui vienodai deklaruoti savo nustatymu sekcijas, formos schema ir validavimo taisykles.
- `ModuleDiagnosticsContract` leidzia moduliui vienodai deklaruoti savo health check, missing files, missing tables ir konfiguracijos busenas.
- `ModuleEventContract` leidzia moduliui vienodai deklaruoti `notifications / activity feed` ivykius.
- `ModuleSearchContract` leidzia moduliui vienodai deklaruoti paieskos saltinius ir ju metaduomenis.
- `ModulePresentationContract` leidzia moduliui vienodai deklaruoti, ka jis rodo korteleje ir ka detaliame rodinyje.

## Admin veiksmu deklaravimas
- `admin: true` ir realus `admin.php` leidzia branduoliui rodyti `Admin` veiksma.
- Jei modulis turi atskira nustatymu vieta, `manifest.json` gali deklaruoti `settings_page`.
- Jei modulis turi atskira diagnostikos ar health vieta, `manifest.json` gali deklaruoti `diagnostics_page`.
- Jei `diagnostics_page` nenurodytas, `administration/infusions.php` kaip bendra fallback sveikatos perziura naudoja `developer mode` detale su anchor `#infusion-dev-<folder>`.
- Branduolio UI modulio veiksmus rodo vienoda tvarka: `Admin`, `Settings`, `Health`, `Upgrade`.

## Ikonografijos taisykles moduliams
- Pagrindine bendra ikon biblioteka visiems moduliams yra `Font Awesome 7`.
- Bendra biblioteka kraunama is `includes/plugins/webicon/fa7/` ir turi buti naudojama navigacijai, busenoms, veiksmams ir tipiniams media simboliams.
- Jei modulis naudoja ikonu klases konfiguracijoje ar DB, pirmenybe teikiama `fa-*` klasems, kad visas UI liktu nuoseklus.
- Papildomas lokalus ikon rinkinys leidziamas tik tada, kai `Font Awesome 7` neturi reikiamu ikon ir yra aiski funkcine arba produkto priezastis.
- Papildomas ikon rinkinys turi buti kraunamas tik to modulio ribose:
- per jo `admin.php`, `panel.php`, viesa `bootstrap` arba modulio `assets/`
- jis negali buti prijungtas globaliai visai temai ar visai admin daliai be atskiro branduolio sprendimo
- Modulis negali perrasyti bendru `fa-*` klasiu, `webfonts` keliu ar bendru temos ikon stiliu.
- Jei modulis naudoja papildoma rinkinį, jis turi:
- tai aprasyti savo dokumentacijoje ir `TODO`
- apriboti CSS selektorius modulio prefiksu ar konteineriu
- neitempti antros pilnos globalios bibliotekos vien del keliu ikon

## Soft disable mode taisykles
- `Soft disable` reiskia, kad modulis lieka idiegtas ir jo duomenys nelieciami, bet jo funkcionalumas laikinai nebeaktyvus.
- Isjungtas modulis neturi:
- registruoti nauju hook'u ar filtru `boot` metu
- rodyti paneliu bendruose puslapiuose
- paleisti foniniu darbu, notification dispatch ar periodiniu uzduociu
- Viesos ir admin nuorodos i isjungta moduli turi buti slepiamos is bendru sarasu.
- Tiesioginis kreipimasis i isjungto modulio puslapi ar route'a turi baigtis saugiai:
- `404`, jei modulis viesai nepasiekiamas
- arba aiskia `modulis isjungtas` busena, jei tam yra administracinis scenarijus
- Isjungimas negali trinti DB lenteliu, duomenu, nustatymu, migraciju logu ar seed'u. Tam yra atskiras `uninstall`.
- Modulio paneles ir view helperiai turi grazinti tuscia arba saugu fallback rezultata, o ne mesti `fatal` ar palikti suluzusi markup.
- Jei modulis turi priklausomybiu ar nuo jo priklauso kiti moduliai, admin UI turi rodyti perspejima apie galimas salutines pasekmes ir integraciju nutrukima.

## ModuleSettingsContract
Jei modulis nori ne tik tureti `settings_page`, bet ir vienodai deklaruoti savo nustatymu struktura, jis gali papildomai igyvendinti `ModuleSettingsContract`.

```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSettingsContract;

final class NewsModule extends AbstractInfusionModule implements ModuleSettingsContract
{
    public function settingsSections(): array
    {
        return [
            [
                'key' => 'general',
                'title' => 'Bendri nustatymai',
                'description' => 'Pagrindines naujienu modulio parinktys',
                'icon' => 'fa fa-gear',
            ],
        ];
    }

    public function settingsFormSchema(): array
    {
        return [
            [
                'key' => 'news_per_page',
                'type' => 'number',
                'label' => 'Naujienu puslapyje',
                'section' => 'general',
                'default' => 10,
            ],
        ];
    }

    public function settingsValidationRules(): array
    {
        return [
            'news_per_page' => [
                'required' => true,
                'type' => 'int',
                'min' => 1,
                'max' => 100,
            ],
        ];
    }
}
```

Paskirtis:
- `settingsSections()`: sekciju metaduomenys (`key`, `title`, `description`, `icon`)
- `settingsFormSchema()`: lauku schema (`key`, `type`, `label`, `section`, `default`, `options`)
- `settingsValidationRules()`: validavimo taisykles pagal lauko rakta

Developer mode per `administration/infusions.php` jau rodo, ar modulis si kontrakta igyvendina, ir kiek sekciju, lauku bei taisykliu jis deklaruoja.

## ModuleDiagnosticsContract
Jei modulis nori ne tik tureti `diagnostics_page`, bet ir vienodai deklaruoti savo diagnostikos duomenis, jis gali papildomai igyvendinti `ModuleDiagnosticsContract`.

```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleDiagnosticsContract;

final class NewsModule extends AbstractInfusionModule implements ModuleDiagnosticsContract
{
    public function diagnosticsHealthChecks(): array
    {
        return [
            [
                'key' => 'table_exists',
                'label' => 'Pagrindine lentele',
                'status' => 'ok',
                'message' => 'Lentele rasta',
            ],
        ];
    }

    public function diagnosticsMissingFiles(): array
    {
        return [];
    }

    public function diagnosticsMissingTables(): array
    {
        return [];
    }

    public function diagnosticsConfigurationStates(): array
    {
        return [
            [
                'key' => 'news_per_page',
                'label' => 'Naujienu puslapyje',
                'status' => 'ok',
                'value' => 10,
                'expected' => '1-100',
                'message' => 'Reiksme tinkama',
            ],
        ];
    }
}
```

Paskirtis:
- `diagnosticsHealthChecks()`: bendri modulio sveikatos patikrinimai
- `diagnosticsMissingFiles()`: trukstami failai ar katalogai
- `diagnosticsMissingTables()`: trukstamos DB lenteles
- `diagnosticsConfigurationStates()`: svarbiu konfiguracijos raktu busenos

Developer mode per `administration/infusions.php` jau rodo, ar modulis si kontrakta igyvendina, kiek jis turi check'u, missing files, missing tables ir konfiguracijos busenu.

## ModuleEventContract
Jei modulis nori vienodai deklaruoti, kokius ivykius jis skelbia i `notification centra`, `activity feed` arba abu, jis gali papildomai igyvendinti `ModuleEventContract`.

```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleEventContract;

final class NewsModule extends AbstractInfusionModule implements ModuleEventContract
{
    public function publishedEvents(): array
    {
        return [
            [
                'key' => 'news.article.published',
                'type' => 'published',
                'title' => 'Naujiena publikuota',
                'summary' => 'Publikuojama nauja naujiena',
                'actor' => ['type' => 'user', 'source' => 'author_id'],
                'target' => ['type' => 'news_article', 'source' => 'id'],
                'visibility' => ['scope' => 'public'],
                'channels' => ['notifications', 'activity_feed'],
            ],
        ];
    }
}
```

Paskirtis:
- `key`: stabilus ivykio identifikatorius
- `type`: ivykio tipas (`created`, `updated`, `published`, `reply`, ...)
- `title` ir `summary`: zmogui suprantami pavadinimas ir santrauka
- `actor` ir `target`: kas sukure ivyki ir ka jis liecia
- `visibility`: kam leidziama ji matyti
- `channels`: ar ivyki reikia siusti i `notifications`, `activity_feed` ar abu

Developer mode per `administration/infusions.php` rodo, ar modulis si kontrakta igyvendina, kiek turi ivykiu ir kiek ju eina i `notifications`, `activity feed` ar abu kanalus.

## ModuleSearchContract
Jei modulis nori vienodai deklaruoti, kaip jo turinys turi buti integruojamas i bendra MiniCMS paieska, jis gali papildomai igyvendinti `ModuleSearchContract`.

```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModuleSearchContract;

final class NewsModule extends AbstractInfusionModule implements ModuleSearchContract
{
    public function searchMetadata(): array
    {
        return [
            [
                'key' => 'news_articles',
                'indexed_fields' => ['title', 'excerpt', 'body', 'tags'],
                'result_url' => 'news.php?id={id}',
                'title' => 'title',
                'summary' => 'excerpt',
                'category' => 'Naujienos',
                'type' => 'article',
                'permission_filter' => 'news.view',
                'weight' => 90,
            ],
        ];
    }
}
```

Paskirtis:
- `indexed_fields`: kurie laukai turi buti indeksuojami
- `result_url`: kaip sugeneruojamas rezultato URL
- `title` ir `summary`: kokie laukai naudojami rezultatui atvaizduoti
- `category` ir `type`: kaip rezultatas kategorizuojamas paieskoje
- `permission_filter`: kokia teisiu taisykle turi buti taikoma
- `weight`: koki svori / relevancijos koeficienti saltinis turi bendrame rezultate

Developer mode per `administration/infusions.php` rodo, ar modulis si kontrakta igyvendina, kiek jis turi paieskos saltiniu, indeksuojamu lauku, URL, permission filter ir svoriu.

## ModulePresentationContract
Jei modulis nori vienodai deklaruoti savo korteles ir detalaus rodinio pateikimo duomenis, jis gali papildomai igyvendinti `ModulePresentationContract`.

```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;
use App\MiniCMS\Infusions\ModulePresentationContract;

final class NewsModule extends AbstractInfusionModule implements ModulePresentationContract
{
    public function presentationMetadata(): array
    {
        return [
            'card' => [
                'badges' => [
                    ['key' => 'featured', 'label' => 'Featured'],
                ],
                'meta' => [
                    ['label' => 'Feed', 'value' => 'RSS / Atom'],
                ],
                'summary' => [
                    'Publikavimas, komentarai ir feed palaikymas.',
                ],
            ],
            'detail' => [
                'sections' => [
                    [
                        'key' => 'capabilities',
                        'title' => 'Galimybes',
                        'items' => ['Komentarai', 'SEO', 'RSS / Atom'],
                    ],
                ],
            ],
        ];
    }
}
```

Rezervuoti core badge raktai:
- `sdk`
- `legacy`
- `has_migrations`
- `upgrade_available`
- `missing_manifest`

Taisykles:
- sie badge priklauso branduoliui ir negali buti perrasomi modulio
- modulis gali deklaruoti papildomus savo badge, meta laukus ir detalaus rodinio sekcijas
- korteleje turi likti tik trumpa santrauka, o detaliame rodinyje gali buti issamesnes sekcijos

Developer mode per `administration/infusions.php` rodo, ar modulis si kontrakta igyvendina, kiek turi korteles badge, meta lauku, santrauku ir detaliu sekciju.

## Modulio struktura
```text
infusions/<modulis>/
  manifest.json
  classes/
  support/
  migrations/
  assets/css/
  assets/js/
  locale/
  admin.php
  panel.php
  schema.php
  upgrade.php
  uninstall.php
```

## Manifest papildymas
SDK moduliai gali nurodyti ne tik `module_class`, bet ir reikalavimus bei galimybes:

```json
{
  "module_class": "App\\News\\NewsModule",
  "min_core_version": "1.0.0",
  "min_php_version": "8.0.0",
  "required_extensions": ["json", "pdo", "pdo_mysql"],
  "dependencies": [],
  "conflicts": [],
  "provides": {
    "panels": ["news"],
    "permissions": ["news.admin"],
    "hooks": [],
    "search_sources": []
  },
  "changelog": [
    {
      "version": "1.1.0",
      "title": "Stabilesne versija",
      "date": "2026-03-29",
      "notes": ["Atnaujintas modulis"]
    }
  ],
  "upgrade_notes": [],
  "rollback_notes": []
}
```

Trumpai apie laukus:
- `min_core_version`: maziausia palaikoma MiniCMS versija
- `min_php_version`: maziausia palaikoma PHP versija
- `required_extensions`: privalomi PHP pletiniai
- `dependencies`: kiti reikalingi moduliai
- `conflicts`: moduliai, su kuriais negalima veikti kartu
- `provides`: ka modulis prideda sistemai (`panels`, `permissions`, `hooks`, `search_sources`)
- `changelog`: versiju istorija
- `upgrade_notes`: svarbios pastabos pries atnaujinima
- `rollback_notes`: svarbios pastabos rollback atvejui

Jei `module_class` nenurodytas, SDK bando rasti klase pagal taisykle:
- `App\\<StudlyFolder>\\<StudlyFolder>Module`

Pvz.:
- `forum` -> `App\\Forum\\ForumModule`
- `user-badges` -> `App\\UserBadges\\UserBadgesModule`

## Modulio klase
```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\AbstractInfusionModule;

final class NewsModule extends AbstractInfusionModule
{
    public function boot(): void
    {
        $this->registerStyle('assets/css/news.css');
        $this->registerScript('assets/js/news.js');
    }
}
```

`AbstractInfusionModule` pagal nutylejima:
- `install()` vykdo `schema.php`
- `upgrade()` vykdo `upgrade.php` kaip fallback
- `uninstall()` vykdo `uninstall.php`
- `renderAdmin()` renderina `admin.php`
- `renderPanel()` renderina `panel.php`

Tai reiskia, kad galima pereiti prie SDK ir nekeisti viso modulio is karto.

Papildomi helperiai moduliui:
- `InfusionContext::migrationsPath()`
- `InfusionContext::hasMigrations()`
- `AbstractInfusionModule::migrationsPath()`
- `AbstractInfusionModule::hasMigrations()`

## Migration naming
Rekomenduojamas pavadinimu formatas:
- `migrations/001_1.0.1.php`
- `migrations/001_1.0.1.rollback.php`

Veikimo taisykle:
- core pirma paleidzia versioned failus is `migrations/`
- jei nauju zingsniu nera, gali buti vykdomas `upgrade.php`
- rollback istorija ir statusai rodomi admin `infusions` puslapyje

## Bendros install / upgrade / rollback taisykles

### Install
- Pries diegima tikrinami `min_core_version`, `min_php_version`, `required_extensions`, `dependencies` ir `conflicts`.
- Install / upgrade / uninstall visada vykdomi po bendru DB lock, kad du administratoriai nepaleistu to paties proceso vienu metu.
- Diegimas turi kurti tik to modulio schema ir pradinius jam reikalingus duomenis.
- Demo turinys turi buti atskirtas nuo bazinio seed ir negali buti privaloma diegimo dalis.
- Jei modulis turi SDK klase, `install()` yra pagrindinis kelias; jei ne, naudojamas `schema.php`.
- Po sekmingo diegimo registruojamos teises, admin meniu, numatyta paneles konfiguracija ir irasoma idiegta versija.

### Upgrade
- Upgrade visada vykdomas tik is dabartines idiegtos versijos i naujesne manifest versija.
- Pirmiausia vykdomi `migrations/` failai pagal versiju tvarka; `upgrade.php` naudojamas tik kaip legacy fallback, kai nera versioned zingsniu.
- Kiekvienas zingsnis turi buti mazas, aiskus, idempotentiskas arba bent jau saugus nuo pakartotinio paleidimo po nesekmes.
- Upgrade negali aklai perrasyti naudotojo turinio, nustatymu ar admin konfiguracijos be aiskaus migration zingsnio.
- Jei keiciasi leidimai, admin meniu ar `provides`, po upgrade jie turi buti persinchronizuojami su manifest.
- Jei moduliui reikia rankinio veiksmo, tai turi buti aprasyta `upgrade_notes`.

### Rollback
- Rollback vykdomas tik jau paleistiems ir nepavykusio upgrade metu uzfiksuotiems zingsniams atbuline tvarka.
- Kiekvienam rollback zingsniui naudojamas atitinkamas `migrations/<version>.rollback.php`, jei jis egzistuoja.
- Jei rollback failo nera, tai turi buti uzloginta kaip `skipped`, o ne tyliai ignoruojama.
- Rollback turi stengtis atstatyti schema ir konfiguracija, bet neturi trinti naudotojo duomenu be labai aiskaus ir dokumentuoto sprendimo.
- Jei upgrade turi negriztamu pokyciu, tai privalo buti aprasyta `rollback_notes`.

### Safe uninstall
- Pries pasalinant moduli, core turi patikrinti, ar nuo jo nepriklauso kiti idiegti moduliai.
- Jei yra priklausomu moduliu, uninstall turi buti blokuojamas su aiskia zinute.
- Admin UI turi parodyti bent minimali paveikiamu duomenu santrauka:
  - core irasai (`infusion_panels`, `infusion_admin_menu`, `infusion_versions`, migraciju logai)
  - modulio DB lenteles ir eiluciu skaicius, jei jos atitinka modulio prefiksa
- Jei salinimas palies duomenis, turi buti reikalaujamas papildomas patvirtinimas, pvz. modulio `folder` ivestis.
- `safe uninstall` nera tas pats, kas `rollback`: rollback skirtas nepavykusiam upgrade, o uninstall yra samoningas modulio pasalinimas.
- Jei modulis turi daug duomenu ar rizikinga salinimo logika, tai turi buti aprasyta `rollback_notes` arba atskiroje admin dokumentacijoje.

### Bendri principai
- Core Installer yra tik branduolio diegimui; moduliu schema ir lifecycle logika lieka `infusions/<modulis>/`.
- `bootstrap.php`, `admin.php` ir `panel.php` turi likti ploni; install/upgrade taisykles neturi virsti nauju monolitiniu helper failu.
- Moduliai neturi daryti pasaliniu veiksmu uz savo ribu be aiskios deklaracijos ir audito.
- Visi rizikingi lifecycle veiksmai turi palikti loga: migration step, rollback step arba audit irasa.

## SimplePanelModule
Jei norite paneles logika laikyti klaseje, galite naudoti:

```php
<?php

namespace App\News;

use App\MiniCMS\Infusions\SimplePanelModule;

final class NewsModule extends SimplePanelModule
{
    protected function panelTitle(array $panelData = []): string
    {
        return 'Naujienos';
    }

    protected function panelBody(array $panelData = []): string
    {
        return '<div class="small text-secondary">Klases pagrindu renderinama panele.</div>';
    }
}
```

Tokiu atveju `panel.php` failas nebera butinas.

## Hook API
Galimi bendri helperiai:
- `infusion_add_hook($name, $listener, $priority = 10)`
- `infusion_do_hook($name, $payload = null, array $context = [])`
- `infusion_apply_filters($name, $value, array $context = [])`

Pavyzdys:
```php
infusion_add_hook('forum.topic.created', function ($payload) {
    return $payload;
});
```

Branduolys jau naudoja hook'us paneliu renderinimui:
- `infusion.panel.output`
- `infusion.panel.output.<folder>`
- `infusion.panel.title`
- `infusion.panel.title.<folder>`

Moduliu gyvenimo ciklui taip pat naudojami hook'ai:
- `before_install`
- `after_install`
- `before_upgrade`
- `after_upgrade`
- `before_uninstall`
- `after_uninstall`

Kiekvienas is ju turi ir modulio-specifini varianta:
- `before_install.<folder>`
- `after_install.<folder>`
- `before_upgrade.<folder>`
- `after_upgrade.<folder>`
- `before_uninstall.<folder>`
- `after_uninstall.<folder>`

Pavyzdinis payload:
```php
[
    'folder' => 'forum',
    'infusion_id' => 1,
    'manifest' => [...],
    'operation' => 'upgrade',
    'installed_version' => '1.0.0',
    'target_version' => '1.1.0',
    'result' => [
        'upgraded' => true,
        'steps' => ['1.1.0'],
    ],
]
```

Taisykles:
- `before_*` hook'ai vykdomi transakcijos viduje pries pagrindini veiksma
- `after_*` hook'ai vykdomi transakcijos viduje po pagrindinio veiksmo, bet pries `commit`
- jei listener'is meta klaida, visa operacija nutraukiama ir esama transakcija / rollback logika perima atstatyma

## Legacy panel API
Sena paneliu sintakse irgi palaikoma:

```php
<?php
openside('Panel Name');
echo 'Lorem ipsum dolor sit amet.';
closeside();
```

Pagalbiniai helperiai:
- `openside()` / `closeside()`
- `opentable()` / `closetable()`
- `render_side_panel($title, $body, array $options = [])`
- `panel_render_current_panel()`
- `panel_render_current_title($default = 'Panele')`

## Paneles ir admin renderinimas
- Jei modulis turi SDK klase, branduolys pirmiausia bando naudoti ja.
- Jei SDK klases nera, branduolys toliau naudoja sena `panel.php` ar `admin.php` faila.
- Jei `panel.php` naudoja `openside()` / `closeside()`, branduolys atpazista, kad modulis jau pats sugeneravo savo wrapperi, ir daugiau jo nebeapgaubia.

## Scaffold generatorius
Nauja moduli galima sugeneruoti:

```powershell
C:\xampp\php\php.exe tools\make-infusion-sdk.php gallery "Galerija" "Galerijos modulis"
```

Generatorius sukuria:
- `manifest.json`
- `classes/<Studly>Module.php`
- `support/README.md`
- `panel.php` su `openside()` / `closeside()` pavyzdziu
- `admin.php`
- `schema.php`
- `upgrade.php`
- `migrations/.gitkeep`
- `migrations/README.md`
- `uninstall.php`
- `locale/lt.php`
- `assets/css/*`
- `assets/js/*`
- `README.md`

## Taisykle
- `includes/classes/MiniCMS/Installer/` yra tik branduolio installeris.
- Moduliu schema, seed'ai, uninstall ir upgrade logika lieka paciuose `infusions/<modulis>/`.
- `migrations/` katalogas yra privaloma modulio strukturos dalis, net jei pradzioje dar tuscias.
- Naujiems moduliams rekomenduojama pirma naudoti `migrations/`, o ne visa atnaujinimo logika krauti i viena `upgrade.php`.
- `support/` katalogas yra rekomenduojamas tada, kai modulyje dar reikia legacy proceduriniu helperiu.
- Venkite vieno monolitinio failo kaip `feature_pack.php`: geriau skaidyti i `support/schema.php`, `support/settings.php`, `support/admin.php` ir pan.
- Jei modulis turi atskirus nustatymus, rekomenduojama kartu su `settings_page` igyvendinti ir `ModuleSettingsContract`, kad branduolys galetu vienodai suprasti jo nustatymu struktura.
- Jei modulis turi atskira diagnostika ar health check logika, rekomenduojama kartu su `diagnostics_page` igyvendinti ir `ModuleDiagnosticsContract`, kad branduolys galetu vienodai suprasti modulio sveikatos duomenis.
- Jei modulis skelbia pranesimu ar activity feed ivykius, rekomenduojama igyvendinti `ModuleEventContract`, kad branduolys galetu vienodai suprasti ivykiu metaduomenis ir kanalus.
- Jei modulis teikia paieskos saltinius, rekomenduojama igyvendinti `ModuleSearchContract`, kad branduolys galetu vienodai suprasti indeksuojamus laukus, rezultatui reikalingus metaduomenis ir leidimu filtra.
- Jei moduliui svarbu vienodai deklaruoti korteles badge ir detalias UI sekcijas, rekomenduojama igyvendinti `ModulePresentationContract`, kad branduolys galetu vienodai suprasti, kas rodoma suvestineje, o kas tik detaliame rodinyje.
