# MiniCMS Pagrindinės Klasės - Paskirtis & Tobulinimas

## Katalogo Struktūra

```
includes/classes/MiniCMS/
├── ProfiledPDO.php           # DB ryšys su query profiliavimų
├── ModuleSettings.php        # Universalus modulio nustatymų valdytojas
├── Auth/
│   └── AuthService.php       # OOP autentifikacijos sritis
├── Mail/
│   └── Mailer.php            # El. pašto sritis (PHPMailer wrapper)
├── Installer/
│   ├── ConfigWriter.php
│   ├── AdminAccountInstaller.php
│   ├── DatabaseInstaller.php
│   ├── DatabaseSchema.php
│   └── (Instaliatoriaus vedlio klasės)
└── Infusions/                # Modulių sistema (žr. Infusions/TODO.md)
```

## Pagrindinės Klasės

### ProfiledPDO
- **Paskirtis**: Plečia PDO su query profiliavimais kūrimo metu
- **Naudojimas**: Kūrimo režimo query sekimas ir našumo stebėjimas
- **Statusas**: ✅ Baigta

### ModuleSettings
- **Paskirtis**: Universalus raktų-verčių nustatymų saugojimas moduliams
- **Pagrindinės savybės**:
  - Raktai su modulio prefiksu (pvz., `downloads_max_file_size`)
  - Automatinis nustatymų kešavimas našumui
  - Naudoja `settings` lentelę
  - Palaiko numatytas reikšmes incijavimo metu
- **Naudojimas**: `new ModuleSettings($pdo, 'module_name', ['key' => 'default'])`
- **Statusas**: ✅ Baigta (v1.0)

### Auth/AuthService
- **Paskirtis**: OOP sąsaja naudotojo autentifikacijai
- **Pagrindinės savybės**:
  - Prisijungimas/atsijungimas su sesijų valdymu
  - Bandymų limitavimas (5 bandymai → 15 min. blokada)
  - Patvirtinimo įvykių registravimas
  - CSRF žetono generavimas/patvirtinimas
- **Statusas**: ✅ Baigta

### Mail/Mailer
- **Paskirtis**: El. pašto sritis naudojant PHPMailer v6.12.0
- **Pagrindinės savybės**:
  - Šablonų palaikymas
  - Daugialypės gavėjų tipai
  - Klaidų registravimas
  - Grįžtas į procedūrinę `send_mail()` funkciją
- **Statusas**: ✅ Baigta

### Installer/* Klasės
- **Paskirtis**: Instaliatoriaus vedlio automatizacija
- **Klasės**:
  - `ConfigWriter`: Sukuria `config.php` iš šablono
  - `DatabaseInstaller`: Sukuria/atnaujina DB schemą
  - `AdminAccountInstaller`: Sukuria pradinį admin naudotoją
  - `DatabaseSchema`: Schemos versijos valdymas
- **Statusas**: ✅ Baigta

## Būsimi Darbai

### Planuojamos Klasės
- [ ] `UserService` - OOP naudotojų valdymas (CRUD operacijos)
- [ ] `PermissionService` - Išplėsti leidimų tikrinimą su kevavimu
- [ ] `Logger` - Centralizuotas registravimas (audit, saugumas, klaidos)
- [ ] `Cache` - Redis/failų paremtas kevaimo sluoksnis
- [ ] `Validator` - Įvesties validavimo taisyklių sistema
- [ ] `Router` - URL maršrutizavimas (jei reikalinga ateityje)

### Kokybės Patobulinimas
- [ ] Unit testai visoms pagrindinėms klasėms
- [ ] Integracijos testai DB operacijoms
- [ ] PHPDoc komentarai visuose public metoduose
- [ ] Klaidų tvarkymo standartizavimas

## Kodavimo Standartai

Visos klasės `/includes/classes/MiniCMS/`:
- **Vardo sritis**: `App\MiniCMS`
- **PSR-4 Autoloading**: per composer.json
- **Priklausomybės**: Įjungiamos per konstruktorių
- **Duomenų bazė**: Naudoja `$GLOBALS['pdo']` arba įjungtą PDO
- **Klaidų tvarkymas**: Išimtys geriau nei tyli nesėkmai
