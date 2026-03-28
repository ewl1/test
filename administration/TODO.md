# Administration TODO

## Paskirtis
- Administracijos puslapiai, dashboard, diagnostika, moderatoriu darbo vieta ir sistemos prieziuros UI.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: locale, kontrastas, diagnostika, clear tools, saugumo patvirtinimai.
- `v1.2`: pranesimu centras, profilio ir security UI, komunikacijos administravimas.
- `v1.4`: versijos, update channel, core update center, hook/debug irankiai.
- `v1.5`: SEO audit, redirect manager, broken links checker.

## Svarbus failai
- `login.php`: atskiras admin prisijungimas.
- `index.php`: dashboard santrauka ir greitos nuorodos.
- `settings.php`, `users.php`, `roles.php`, `permissions.php`: pagrindinis valdymo branduolys.
- `diagnostics.php`, `audit-logs.php`, `error-logs.php`: diagnostika ir stebesena.
- `infusions.php`: moduliu versijos, busenos ir valdymas.

## Likusieji darbai

### Admin kokybe ir locale
- [ ] Uzbaigti visu administracijos formu ir antriniu puslapiu tekstu perkelima i locale raktus.
- [ ] Uzbaigti kontrasto audita visoms maziau naudojamoms kortelems, badge ir lenteliu busenoms.
- [ ] Suvienodinti filtrus, paieska, rusiavima ir `bulk actions` administracijos lentelese.

### Diagnostika ir versijos
- [ ] Diagnostikoje galutinai suskirstyti pletinius i `butini`, `rekomenduojami`, `pasirenkami`.
- [ ] Diagnostics puslapyje rodyti MiniCMS versija, PHP versija, update channel ir OPcache busena.
- [ ] `infusions.php` rodyti `installed version`, `manifest version` ir `available upgrade`.
- [ ] Paruosti atskira `updates.php` arba panasu puslapi core ir infusion atnaujinimams.

### Admin irankiai ir saugumas
- [ ] Prideti `Clear cache / Clear rate limits / Clear reset tokens` irankius.
- [ ] Prideti admin action confirmations jautriems veiksmams.
- [ ] Prideti login alerts ir suspicious activity perziuros vieta administracijoje.
- [ ] Prideti password reset audit ir upload quarantine perziuros vieta.
- [ ] Prideti `read-only maintenance mode` valdyma administracijoje.

### Komunikacija ir moderavimas
- [ ] Paruosti moderatoriu `moderation queue` puslapi.
- [ ] Prideti pranesimu centro administravima ir sistemos pranesimu siuntima.
- [ ] Paruosti privaciu zinuciu prieziuros ir abuse/report perziuros ekranus.

### SEO ir techninis auditavimas
- [ ] Prideti `SEO audit` puslapi.
- [ ] Prideti `redirect manager` (`301/302`) administravima.
- [ ] Prideti `broken links checker` rezultatu perziura.
- [ ] Jei bus reikalinga, prideti `hook debugger` ir `dependency graph` diagnostikos vaizda.
