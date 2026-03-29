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
- [ ] Nuspresti, kaip administracijoje bus naudojamos ikonos: navigacija, lenteliu veiksmai, statusai, diagnostika.
- [ ] Ikonoms administracijoje taikyti taisykle: tekstas + ikona svarbiems veiksmams, vien ikona tik antriniams veiksmams.

### Panels valdymas
- [ ] `panels.php` prideti paneliu matomuma pagal role ir puslapi.
- [ ] Prideti paneles tipo ir saltinio rodyma: branduolio, infusion, custom.
- [ ] Prideti paneliu perziura (`preview`) ir trumpa informacija, kur panele rodoma.
- [ ] Prideti `duplicate / delete / reset defaults` veiksmus paneliu valdyme.
- [ ] Prideti layout `import / export` ir `restore default layout`.
- [ ] Prideti paneliu cache valdyma ir badge, ar panele cache'inama.
- [x] Pagerinti drag-drop UX: aiskesni placeholder, `drop zone`, aktyvia zonos busena ir issaugojimo patvirtinima.
- [ ] Prideti paneles mini perziuros kortele su piktograma, trumpu aprasu ir matomumo badge.
- [ ] Apsvarstyti responsive `panels` valdyma telefonu ir planseteje: ne tik drag-drop, bet ir alternatyvu perkrovimo/isdestymo rezima.
- [ ] Suderinti `panels.php` su bendru layout system, kad butu aisku, kuri zona yra `content`, `left sidebar`, `right sidebar`, `full-width`.

### Diagnostika ir versijos
- [ ] Diagnostikoje galutinai suskirstyti pletinius i `butini`, `rekomenduojami`, `pasirenkami`.
- [ ] Diagnostics puslapyje rodyti MiniCMS versija, PHP versija, update channel ir OPcache busena.
- [ ] Diagnostics puslapyje rodyti planuoklio / cron busena: paskutinis paleidimas, paskutine klaida, sekantys darbai.
- [ ] Jei sistemoje dar yra `install.php`, diagnostics puslapyje rodyti kritini admin-only ispejima: `DEMESIO: rastas install.php failas, nedelsiant ji istrinkite!`.
- [x] `infusions.php` rodyti `installed version`, `manifest version` ir `available upgrade`.
- [ ] Paruosti atskira `updates.php` arba panasu puslapi core ir infusion atnaujinimams.

### Infusions valdymas
- [ ] `infusions.php` prideti filtrus pagal busena: idiegta, ijungta, isjungta, turi atnaujinima.
- [x] Rodyti modulio suderinamuma su MiniCMS versija, PHP versija ir reikalaujamais pletiniais.
- [x] Rodyti modulio priklausomybes ir konfliktus.
- [ ] Rodyti, ka modulis prideda: route'us, teises, paneles, paieskos saltinius, hook'us.
- [ ] Prideti aiskesni `upgrade preview`: changelog, migracijos zingsniai, rollback pastabos.
- [x] Prideti modulio sveikatos patikra: ar yra manifest, locale, assets, admin failas, schema.
- [ ] Prideti aiskius moduliu badge:
- `SDK`
- `Legacy`
- `Has migrations`
- `Upgrade available`
- `Missing manifest`
- [ ] Padaryti aiskesni moduliu saraso vizualini atskyrima: pavadinimas, folder, versija, sveikatos badge ir pagrindinis veiksmas.
- [x] Pagerinti `infusions.php` veiksmu matomuma: vieningas veiksmu blokas, ryskesni CTA ir aiskesnis veiksmu grupavimas.
- [ ] Prideti `grid / table` rodymo rezimus, jei moduliu kiekis isaugs.
- [ ] Isryskinti moduliu `folder`, `slug`, busena ir veiksmus vienodomis admin badge taisyklemis.
- [ ] Prideti ikonas modulio tipui, sveikatos busenai, atnaujinimui ir pagrindiniams veiksmams.
- [ ] Prideti modulio detales vaizda arba sonine perziura:
- versijos
- priklausomybes ir konfliktai
- teises, admin meniu, paneles, hook'ai
- assets, locale, migrations ir paskutiniai upgrade logai
- [ ] Prideti aisku `developer mode` rodini `infusions.php`, kuriame butu matomi `module_class`, registruoti hook'ai, manifest laukai ir diagnostikos kontraktai.
- [ ] Prideti `safe uninstall` UI:
- priklausomu moduliu perspejimai
- duomenu kiekiu ar lenteliu santrauka
- papildomas patvirtinimas pries pasalinima
- [ ] Prideti modulio `settings` ir `diagnostics` nuorodu/logikos sutarti, kad admin UI vienodai rodytu, ka modulis moka.
- [ ] Prideti modulio `import / export` veiksmu vieta ateities konfiguraciju ir preset'u keitimuisi.

### Admin irankiai ir saugumas
- [ ] Prideti `Clear cache / Clear rate limits / Clear reset tokens` irankius.
- [ ] Prideti admin action confirmations jautriems veiksmams.
- [ ] Admin dashboard rodyti kritini admin-only pranesima, jei po idiegimo paliktas `install.php` failas.
- [ ] Prideti `scheduled tasks` / `cron` valdymo puslapi: sarasas, paskutinis vykdymas, rankinis paleidimas.
- [ ] Prideti admin santrauka automatiniams darbams: email queue, log rotation, search reindex, sitemap rebuild, cleanup.
- [ ] Prideti `captcha` nustatymu puslapi arba skilti: kur ijungta, koks provideris, kada eskaluoti po rate-limit.
- [ ] Diagnostikoje rodyti, ar `captcha` sluoksnis sukonfiguruotas ir kuriems srautams aktyvus.
- [ ] Prideti login alerts ir suspicious activity perziuros vieta administracijoje.
- [ ] Prideti password reset audit ir upload quarantine perziuros vieta.
- [ ] Prideti `read-only maintenance mode` valdyma administracijoje.

### Komunikacija ir moderavimas
- [ ] Paruosti moderatoriu `moderation queue` puslapi.
- [ ] Prideti pranesimu centro administravima ir sistemos pranesimu siuntima.
- [ ] Paruosti privaciu zinuciu prieziuros ir abuse/report perziuros ekranus.
- [ ] Paruosti asmeniniu zinuciu administravimo/diagnostikos santrauka: abuse atvejai, flood, blokavimai, archyvavimo busenos.

### SEO ir techninis auditavimas
- [ ] Prideti `SEO audit` puslapi.
- [ ] Prideti `redirect manager` (`301/302`) administravima.
- [ ] Prideti `broken links checker` rezultatu perziura.
- [ ] Jei bus reikalinga, prideti `hook debugger` ir `dependency graph` diagnostikos vaizda.
- [ ] Atlikti admin responsive audita bent telefonu, planseteje ir 1366px plocio ekrane.
