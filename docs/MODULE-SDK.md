# Module SDK

## Paskirtis
- `MiniCMS Module SDK` leidzia kurti infusion modulius vienodu principu.
- SDK nepanaikina seno `admin.php / panel.php / schema.php / upgrade.php` modelio, o ji apgaubia ir leidzia pereiti prie klasiu palaipsniui.

## Branduolio dalys
- `App\MiniCMS\Infusions\InfusionManifest`
- `App\MiniCMS\Infusions\InfusionContext`
- `App\MiniCMS\Infusions\InfusionModuleInterface`
- `App\MiniCMS\Infusions\AbstractInfusionModule`
- `App\MiniCMS\Infusions\HookRegistry`
- `App\MiniCMS\Infusions\InfusionSdk`
- `App\MiniCMS\Infusions\ModuleScaffolder`

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
- `upgrade()` vykdo `upgrade.php`
- `uninstall()` vykdo `uninstall.php`
- `renderAdmin()` renderina `admin.php`
- `renderPanel()` renderina `panel.php`

Tai reiskia, kad galima pereiti prie SDK ir nekeisti viso modulio is karto.

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

## Paneles ir admin renderinimas
- Jei modulis turi SDK klase, branduolys pirmiausia bando naudoti ja.
- Jei SDK klases nera, branduolys toliau naudoja sena `panel.php` ar `admin.php` faila.

## Scaffold generatorius
Nauja moduli galima sugeneruoti:

```powershell
C:\xampp\php\php.exe tools\make-infusion-sdk.php gallery "Galerija" "Galerijos modulis"
```

Generatorius sukuria:
- `manifest.json`
- `classes/<Studly>Module.php`
- `panel.php`
- `admin.php`
- `schema.php`
- `migrations/.gitkeep`
- `uninstall.php`
- `locale/lt.php`
- `assets/css/*`
- `assets/js/*`
- `README.md`

## Taisykle
- `includes/classes/MiniCMS/Installer/` yra tik branduolio installeris.
- Moduliu schema, seed'ai, uninstall ir upgrade logika lieka paciuose `infusions/<modulis>/`.
- `migrations/` katalogas yra privaloma modulio struktūros dalis, net jei pradžioje dar tuščias.
