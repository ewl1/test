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
- `panels.php`: paneliu kurimas, isdestymas ir ateities paneliu valdymo centras.
- `diagnostics.php`, `audit-logs.php`, `error-logs.php`: diagnostika ir stebesena.
- `infusions.php`: moduliu versijos, busenos ir valdymas.

## Likusieji darbai

### Admin kokybe ir locale
- [ ] Uzbaigti visu administracijos formu ir antriniu puslapiu tekstu perkelima i locale raktus.
- [ ] Uzbaigti kontrasto audita visoms maziau naudojamoms kortelems, badge ir lenteliu busenoms.
- [ ] Suvienodinti filtrus, paieska, rusiavima ir `bulk actions` administracijos lentelese.
- [ ] Suvienodinti admin `design system`: korteles, lenteles, badge, mygtukai, formu laukai ir pagalbiniai tekstai.
- [ ] Prideti aiskesnes tuscias busenas, klaidu busenas ir sekmes pranesimu pateikima administracijoje.
- [ ] Apsvarstyti tankesni (`dense`) lenteliu rezima ir patogesni filtravimo juostos isdestyma.

### Panels valdymas
- [ ] `panels.php` prideti paneliu matomuma pagal role ir puslapi.
- [ ] Prideti paneles tipo ir saltinio rodyma: branduolio, infusion, custom.
- [ ] Prideti paneliu perziura (`preview`) ir trumpa informacija, kur panele rodoma.
- [ ] Prideti `duplicate / delete / reset defaults` veiksmus paneliu valdyme.
- [ ] Prideti layout `import / export` ir `restore default layout`.
- [ ] Prideti paneliu cache valdyma ir badge, ar panele cache'inama.
- [ ] Pagerinti drag-drop UX: aiskesni placeholder, `drop zone`, aktyvia zonos busena ir issaugojimo patvirtinima.
- [ ] Prideti paneles mini perziuros kortele su piktograma, trumpu aprasu ir matomumo badge.

### Diagnostika ir versijos
- [ ] Diagnostikoje galutinai suskirstyti pletinius i `butini`, `rekomenduojami`, `pasirenkami`.
- [ ] Diagnostics puslapyje rodyti MiniCMS versija, PHP versija, update channel ir OPcache busena.
- [ ] `infusions.php` rodyti `installed version`, `manifest version` ir `available upgrade`.
- [ ] Paruosti atskira `updates.php` arba panasu puslapi core ir infusion atnaujinimams.

### Infusions valdymas
- [ ] `infusions.php` prideti filtrus pagal busena: idiegta, ijungta, isjungta, turi atnaujinima.
- [ ] Rodyti modulio suderinamuma su MiniCMS versija, PHP versija ir reikalaujamais pletiniais.
- [ ] Rodyti modulio priklausomybes ir konfliktus.
- [ ] Rodyti, ka modulis prideda: route'us, teises, paneles, paieskos saltinius, hook'us.
- [ ] Prideti aiskesni `upgrade preview`: changelog, migracijos zingsniai, rollback pastabos.
- [ ] Prideti modulio sveikatos patikra: ar yra manifest, locale, assets, admin failas, schema.
- [ ] Padaryti aiskesni moduliu saraso vizualini atskyrima: pavadinimas, folder, versija, sveikatos badge ir pagrindinis veiksmas.
- [ ] Prideti `grid / table` rodymo rezimus, jei moduliu kiekis isaugs.
- [ ] Isryskinti moduliu `folder`, `slug`, busena ir veiksmus vienodomis admin badge taisyklemis.

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
