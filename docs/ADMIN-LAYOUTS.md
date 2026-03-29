# Admin Layouts

## Paskirtis
- Vieningi administracijos isdestymo presetai, kad `dashboard`, lenteles, formos, nustatymai ir diagnostika neaugtu kaip atskiri vienkartiniai puslapiai.

## Failai
- `themes/default/admin_layout.php`: helperiai puslapio antrastei ir statistikos juostai.
- `themes/default/admin.css`: bendros admin layout klases.

## Presetai

### `dashboard`
- Wrapper: `admin-layout admin-layout-dashboard`
- Naudokite su `admin_render_page_header([... 'variant' => 'dashboard'])`
- Tinka santraukoms, greitoms nuorodoms, KPI kortelems.

### `table-view`
- Wrapper: `admin-layout admin-layout-table-shell`
- Papildomi blokai:
  - `admin-layout-table-toolbar`
  - `table-responsive`
- Tinka puslapiams kaip `users.php`, `roles.php`, `audit-logs.php`, `error-logs.php`.

### `form-view`
- Wrapper: `admin-layout admin-layout-form-shell`
- Papildomi blokai:
  - `admin-layout-form-actions`
- Tinka puslapiams su viena pagrindine forma ir aiskiais veiksmu mygtukais.

### `split-settings`
- Wrapper: `admin-layout admin-layout-split-settings`
- Grid: `row g-4 admin-layout-split`
- Kolonos:
  - pagrindinis turinys: `admin-layout-main`
  - sonine juosta: `admin-layout-sidebar`
- Tinka `settings.php` ir moduliu admin nustatymams, kur yra pagrindine forma ir sonines korteles.

### `diagnostics`
- Wrapper: `admin-layout admin-layout-diagnostics admin-layout-diagnostics-shell`
- Naudokite su `admin_render_page_header([... 'variant' => 'diagnostics'])`
- Papildomi blokai:
  - `admin_render_stat_strip([...])`
  - `row g-4 admin-layout-diagnostics-grid`
- Tinka serverio busenai, health check ir runtime suvestinems.

## Helperiai

### `admin_render_page_header(array $config)`
- `title`
- `subtitle`
- `variant`
- `badge_html`
- `actions`

### `admin_render_stat_strip(array $items)`
- `label`
- `value`
- `tone`: `default`, `info`, `success`, `warning`
- `icon`

## Naudojimo kryptis
- Nauji admin puslapiai turi rinktis viena is presetu, o ne kurti nauja layout nuo nulio.
- Jei puslapis netelpa i viena preseta, pirmiausia reikia isskirti nauja bendra preseta, o ne vienkartini CSS.
- `dashboard` ir `diagnostics` jau pervesti ant naujo helperio.
- Tolimesni kandidatai:
  - `users.php` -> `table-view`
  - `roles.php` -> `table-view`
  - `settings.php` -> `split-settings`
  - `infusions.php` -> `table-view` arba ateityje `compact/detail`
