# MiniCMS Modulių Sistemos Klasės - Paskirtis & Tobulinimas

## Katalogo Struktūra

```
includes/classes/MiniCMS/Infusions/
├── InfusionManifest.php                # manifest.json analizatorius
├── InfusionContext.php                 # Modulio vykdymo kontekstas
├── InfusionSdk.php                     # Pagrindinė SDK moduliams
├── AbstractInfusionModule.php           # Bazinė klasė moduliams
├── InfusionModuleInterface.php          # Modulio sutarties sąsaja
├── HookRegistry.php                     # Hook sistema (fire/listen)
├── ModuleScaffolder.php                 # Modulio generatoriaus CLI įrankis
├── ModuleSettingsContract.php           # Neprivaloma: Nustatymai UI
├── ModuleSearchContract.php             # Neprivaloma: Globalus paieškos integration
├── ModuleEventContract.php              # Neprivaloma: Hook deklaracijos
├── ModulePresentationContract.php       # Neprivaloma: Frontend skydelio widget
└── ModuleDiagnosticsContract.php        # Neprivaloma: Sistema diagnostikos skirtukas
```

## Pagrindinės Klasės

### InfusionManifest
- **Paskirtis**: Analizuoti ir patvirtinti `manifest.json` moduliams
- **Analizuoti duomenys**:
  - Modulio metaduomenys (pavadinimas, versija, aprašymas)
  - Deklaruoti leidimai
  - Hook deklaracijos
  - Admin meniu įrašas
  - SDK versijos sudrasumas
  - Sutarties deklaracijos
- **Statusas**: ✅ Baigta

### InfusionContext
- **Paskirtis**: Įkrauto modulio vykdymo aplinka
- **Suteikia**:
  - Modulio katalogo kelius
  - Duomenų bazės prieigą
  - Konfigūracijos nustatymus
  - Įvykių svaidymą/klausimą
- **Statusas**: ✅ Baigta

### InfusionSdk
- **Paskirtis**: Pagrindinė modulio kūrėjų pradžios taškas
- **Pagrindiniai metodai**:
  - `loadModules()` - Įkelkite visus įgalintus modulius
  - `fireHook($name, $data)` - Suaktyvinti įvykius
  - `registerPermission()` - Apibrėžkite modulio leidimus
  - `addPanel()` - Registruokite frontend skydelį
  - `addAdminMenu()` - Pridėkite admin puslapį
- **Statusas**: ✅ Baigta

### AbstractInfusionModule
- **Paskirtis**: Bazinė klasė OOP stiliaus moduliams
- **Suteikia**:
  - `bootstrap()` - Modulio inicializavimas
  - `onInstall()` - Sąranka pirmą kartą įdiegus
  - `onUpgrade()` - Paleisti versijos atnaujinimo metu
  - `onUninstall()` - Išvalymas pašalinimo metu
- **Statusas**: ✅ Baigta

### HookRegistry
- **Paskirtis**: Įvykių sistema tarpmodulinei komunikacijai
- **Metodai**:
  - `HookRegistry::on('event.name', callback)` - Klausyti hook
  - `HookRegistry::fire('event.name', data)` - Suaktyvinti hook
- **Pavyzdiniai Hook'ai**:
  - `forum.topic.created`
  - `user.registered`
  - `download.uploaded`
  - (Modulis gali deklaruoti savus hook'us manifest'e)
- **Statusas**: ✅ Baigta

### ModuleScaffolder
- **Paskirtis**: CLI įrankis naujo modulio šablonui generuoti
- **Komanda**: `php tools/make-infusion-sdk.php`
- **Sukuria**:
  - Modulio katalogų struktūrą
  - `manifest.json`
  - Šabloniniai failai (bootstrap.php, admin.php, schema.php, ir kt.)
  - Leidimų apibrėžimus
- **Statusas**: ✅ Baigta

## Neprivalaomos Sutartys (Interfacai)

### ModuleSettingsContract
- **Paskirtis**: Nustatymo UI teikimas admin skydelyje (skirtukas admin lange)
- **Diegti jei**: Modulis turi būti konfigūruojamas forma
- **Reikalingi metodai**:
  - `getSettingsSchema()` - Apibrėžkite formos laukus
  - `saveModuleSettings()` - Apdorokite formos atsiųstą laiką
  - `renderSettingsForm()` - Nubraižykite nustatymo UI
- **Statusas**: ⚠️ Dalinis (reikalingas formos nubraižymas)

### ModuleSearchContract
- **Paskirtis**: Integruokite modulio turinį į globalią svetainės paiešką
- **Diegti jei**: Modulis turi ieškomą turinį (forumo pranešimai, atsisiuntimai, ir kt.)
- **Reikalingi metodai**:
  - `search(string $query)` - Grąžinti sutampančius rezultatus
  - `getSearchResultType()` - Rezultato kategorijos pavadinimas
- **Statusas**: ✅ Baigta

### ModuleEventContract
- **Paskirtis**: Deklaruoti hook'us kuriuos modulis svaidytas (dokumentacijai)
- **Diegti jei**: Modulis naudoja paprastus hook'us
- **Reikalingi metodai**:
  - `getHookDeclarations()` - Grąžinti hook'o schemą
- **Statusas**: ✅ Baigta

### ModulePresentationContract
- **Paskirtis**: Nubraižyti skydelį widget'ą frontend'e
- **Diegti jei**: Modulis rodo turinį pradiniame pulapyje/šaltinėje
- **Reikalingi metodai**:
  - `renderPanel()` - Grąžinti HTML skydeliui
  - `getPanelOptions()` - Admin skydelio nustatymo parinkitės
- **Statusas**: ✅ Baigta

### ModuleDiagnosticsContract
- **Paskirtis**: Teikti sveikatos patikras admin diagnostikos puslapyje
- **Diegti jei**: Modulis turi sistemos stebėjimo poreikius
- **Reikalingi metodai**:
  - `runDiagnostics()` - Grąžinti sveikatos būsenos masyvą
  - `getDiagnosticLabel()` - Rodymo pavadinimas
- **Statusas**: ✅ Baigta

## Modulio Kūrimo Srautas

1. **Sukurti Modulį**
   ```
   php tools/make-infusion-sdk.php modulevadinimas "Modulio Žmogaus Pavadinimas"
   ```

2. **Apibrėžti Schemą** (`schema.php`)
   ```php
   CREATE TABLE infusion_modulevadinimas (...);
   ```

3. **Deklaruoti Manifest** (`manifest.json`)
   ```json
   {
     "name": "Modulio Pavadinimas",
     "version": "1.0.0",
     "contracts": ["settings", "search", "presentation"]
   }
   ```

4. **Diegti Bootstrap** (`bootstrap.php`)
   ```php
   HookRegistry::on('some.hook', function($data) { ... });
   HookRegistry::fire('module.loaded');
   ```

5. **Pridėti Admin Puslapį** (`admin.php`)
   - Jei modulis diegdžia `ModuleSettingsContract`, admin skirtukas auto-atsiranda
   - Gali naudoti infusion-admin.php wrapper

6. **Pridėti Frontend Widget** (`panel.php`)
   - Jei diegdžia `ModulePresentationContract`, nubraižo pradiniame pulapyje

7. **Testuoti & Diegti**
   - Modulis įgalintas per Admin → Moduliai → Įgalinti
   - Gali išjungti/pašalinti be duomenų praradimo

## Būsimi Patobulinimul

### Planuojami
- [ ] Modulio priklausomybės sistema (Modulis A reikia Modulio B)
- [ ] Versijos migracija sistema (auto-run atnaujinimai)
- [ ] Modulio rinkodara API (parsisiųsti/įdiegti iš repo)
- [ ] Išplėtinti leidimų UI (per-modulio leidimų priskyrimas)
- [ ] API endpoint auto-registracija (REST API moduliams)

### Gražioji už tai
- [ ] Modulio našumo profiliavimas
- [ ] Modulio turto surišimas (CSS/JS minifikacija)
- [ ] Modulio karštas perkrova (kūrimo režimas)
- [ ] Modulio izoliacija (riboti duomenų bazės prieigą)

## Dabartiniai Moduliai

| Modulis | Kelias | Versija | Statusas |
|---------|--------|---------|----------|
| Forumas | `/infusions/forum/` | 2.0.0 | ✅ Aktyvus |
| Naujienos | `/infusions/news/` | 1.1.0 | ✅ Aktyvus |
| Atsisiuntimai | `/infusions/downloads/` | 1.0.1 | ✅ Aktyvus |
| Šauksas | `/infusions/shoutbox/` | 1.0.0 | ✅ Aktyvus |

(Pridėkite naujus modulius čia sukūrę juos)
