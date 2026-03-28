 # Mini CMS Pro v4

## Kas sutvarkyta
- tik `/administration/`
- tik `/includes/`
- tik `/themes/`
- `maincore.php` centralizuoja branduolį ir kelius
- `install.php` nebedubliuoja kelio logikos
- `site_settings` iškelti į DB
- atskiri temos pasirinkimai svetainei ir administracijai
- `images/favicons/`
- `showMemoryUsage()`, `showcounter()`, `showbanners()`, `showcopyright()`
- `navigation_links` su sublinks palaikymu DB
- `infusions` ir `infusion_panels` bazė
- `includes/functions/pagination.php`

## Pastabos
- realus WYSIWYG sąmoningai nejungtas
- drag & drop panelėms dar ne pilnas: branduolio ir DB bazė yra, UI kol kas form-based
- Bootstrap komponentų bazė paruošta per temą ir admin nustatymus, bet tai dar nėra pilna komponentų biblioteka


## v5 papildymai
- prijungtas SortableJS
- pilnesnis `administration/panels.php` su drag & drop tarp pozicijų
- veikiantys `administration/roles.php`
- veikiantis `administration/permissions.php`
- veikiantis `administration/users.php`
- išplėstas `administration/infusions.php` su enable/disable/uninstall


## v6 papildymai
- realus infusion lifecycle iš failų sistemos:
  - `scan_infusions()`
  - `install_infusion_from_folder()`
  - `uninstall_infusion_by_id()`
  - `load_enabled_infusions()`
- tikras infusion loaderis iš `/infusions/`
- panelių renderinimas frontende iš DB pozicijų per `render_panels()`
- pavyzdinės infusions:
  - `infusions/news`
  - `infusions/forum`
- smulkesnis permissions tikrinimas pagal veiksmus:
  - `users.view`
  - `users.create`
  - `users.edit`
  - `users.status`
  - `users.delete`
  - `roles.manage`
  - `permissions.manage`
- pilnesnis users/roles validation sluoksnis:
  - `includes/validation.php`


## v7 papildymai
- infusion admin standartas:
  - `manifest.json`
  - `schema.php`
  - `upgrade.php`
  - `admin.php`
  - `panel.php`
  - `uninstall.php`
- `administration/infusion-admin.php`
- manifest/schema/version upgrade sistema
- `infusion_versions` lentelė
- admin puslapyje galima:
  - install
  - enable / disable
  - upgrade
  - uninstall
  - atsidaryti infusion admin

## Kam skirtas Manifest?
`manifest.json` yra trumpas aprašymo failas apie infusion. Jis leidžia branduoliui suprasti:
- koks modulio pavadinimas
- kokia jo versija
- ar turi admin dalį
- ar turi panelę
- ar turi schemą ir upgrade žingsnius
- kokia numatyta panelės pozicija
- kokie minimalūs branduolio reikalavimai

Trumpai: manifestas leidžia **modulį atpažinti, įdiegti, rodyti admin sąraše ir saugiai atnaujinti** be hardcode kiekvienam moduliui.


## v8 papildymai
- manifest permission/admin menu standartas
- `infusion_admin_menu` lentelė
- `register_infusion_permissions()`
- `register_infusion_admin_menu()`
- automatinė infusion admin registracija į administracijos meniu
- `administration/infusion-admin.php` naudoja manifesto admin standartą

### Ką dabar gali aprašyti manifestas
- `permissions`: kokias teises sukurti
- `admin_menu`: kokius admin meniu punktus užregistruoti

### Kam reikalingi papildomi mechanizmai

**Dependency tikrinimas tarp infusionų**  
Tai skirta atvejui, kai vienas modulis remiasi kitu. Pvz. galerija naudoja komentarų infusion. Jei komentarų nėra, galerijos admin ar schema gali lūžti.

**Migration step logika per versijų grandinę**  
Dabar atnaujinimas paleidžia vieną `upgrade.php`. Tai tinka paprasčiau schemai. Bet jei modulis keliauja per 1.0.0 → 1.1.0 → 1.2.0 → 2.0.0, saugiau turėti aiškius žingsnius kiekvienai versijai, nes ne visi atnaujinimai būna vienodi. Taip, modulis turi atsinaujinti, bet praktiškai saugiau, kai jis tai daro etapais.

**Rollback mechanizmas**  
Taip — būtent tam, kad mažėtų rizika prarasti duomenis ar likti pusiau atnaujintoje būsenoje, jei upgrade nutrūksta per vidurį. Paprasčiausias variantas yra DB backup + transakcijos + versijos neatnaujinimas, jei žingsnis nepavyko.


## v9 papildymai
- dependency tikrinimas branduolio lygiu
- migration steps branduolio lygiu per `/infusions/<folder>/migrations/`
- rollback branduolio lygiu per `.rollback.php` step failus
- `infusion_migration_log`
- `infusion_rollback_log`

### Kaip dabar veikia upgrade
1. nuskaitomas manifestas
2. patikrinamos priklausomybės
3. nustatoma įdiegta versija ir target versija
4. jei yra `/migrations/`, vykdomi visi tarpiniai step failai iki target versijos
5. jei step nepavyksta, rollback bando grąžinti atgal jau įvykdytus žingsnius
6. jei viskas pavyksta, įrašoma nauja versija į `infusion_versions`

### Branduoliui dar siūlyčiau pridėti
- sisteminį event/hook dispatcher
- centralizuotą cache sluoksnį
- DB migracijų lock mechanizmą, kad du adminai vienu metu nepaleistų upgrade
- soft delete + recycle bin kritiniams objektams
- config override per `.env`
- failų saugyklos abstrakciją (local / s3 / ftp)
- background job / queue sistemą
- notification center
- health check / diagnostics puslapį
- core updaterį su checksum tikrinimu
- audit log peržiūros UI su filtrais
- ACL cache, kad permissions neklaustų DB kas kartą


## v10 papildymai
- pradinis `Module SDK` branduolys:
  - `InfusionManifest`
  - `InfusionContext`
  - `InfusionModuleInterface`
  - `AbstractInfusionModule`
  - `HookRegistry`
  - `InfusionSdk`
  - `ModuleScaffolder`
- dinaminis moduliu namespace autoload pagal `infusions/<modulis>/classes/`
- SDK-aware `install / upgrade / uninstall / admin / panel` srautai
- `tools/make-infusion-sdk.php` generatorius naujiems moduliams
- pirmas gyvas SDK modulis: `infusions/news/classes/NewsModule.php`

## Kur skaityti apie SDK
- `docs/MODULE-SDK.md`
