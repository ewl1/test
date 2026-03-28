# Module SDK

## Paskirtis
- `MiniCMS Module SDK` leidzia kurti infusion modulius vienodu principu.
- SDK nepanaikina seno `admin.php / panel.php / schema.php / upgrade.php` modelio, o ji apgaubia ir leidzia pereiti prie klasiu palaipsniui.

## Branduolio dalys
- `App\MiniCMS\Infusions\InfusionManifest`
- `App\MiniCMS\Infusions\InfusionContext`
- `App\MiniCMS\Infusions\InfusionModuleInterface`
- `App\MiniCMS\Infusions\AbstractInfusionModule`
- `App\MiniCMS\Infusions\SimplePanelModule`
- `App\MiniCMS\Infusions\HookRegistry`
- `App\MiniCMS\Infusions\InfusionSdk`
- `App\MiniCMS\Infusions\ModuleScaffolder`

## Kas nauja dabartiniame modelyje
- `migrations/` yra pirmaeilis modulio atnaujinimu kelias.
- `upgrade.php` lieka kaip legacy fallback, jei nera vykdytinu versioned migration failu.
- Core automatikai uzdeda DB lock per `install / upgrade / uninstall`.
- `administration/infusions.php` rodo aktyvu migraciju lock, paskutinius zingsnius ir rollback istorija.

## Modulio struktura
```text
infusions/<modulis>/
  manifest.json
  classes/
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
SDK moduliai gali nurodyti:

```json
{
  "module_class": "App\\News\\NewsModule"
}
```

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
