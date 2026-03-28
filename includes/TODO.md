# Includes TODO

## Paskirtis
- Bendri helperiai, saugumo sluoksnis, autentikacija, locale, pastas, profiliai, komentarai ir sistemines paslaugos.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: locale, saugumas, audit, smoke test helperiai.
- `v1.2`: komunikacijos ir profilio logika.
- `v1.4`: branduolio registrai, atnaujinimai ir paslaugu sluoksnis.
- `v1.5`: SEO ir optimizacijos branduolio paslaugos.

## Svarbus failai
- `bootstrap.php`: sesijos, saugumo antrastes, bendras ikrovimas.
- `locale.php`: locale failu uzkrovimas branduoliui ir moduliams.
- `auth.php`, `security.php`, `permissions.php`, `validation.php`: pagrindinis saugumo ir prisijungimo sluoksnis.
- `password_resets.php`, `mail.php`: slaptazodzio atstatymas ir el. laiskai.
- `user_profiles.php`: profilio reitingai, komentarai ir vieso profilio statistika.

## Likusieji darbai

### Komunikacija ir paskyros
- [ ] Paruosti asmeniniu zinuciu duomenu modeli: pokalbiai, zinutes, archyvas, soft delete.
- [ ] Prideti unread skaiciavimo logika privacioms zinutems ir pranesimu centrui.
- [ ] Prideti naudotoju blokavimo logika zinutems ir kontaktams.
- [ ] Isgryninti bendra `notifications` varikli visam MiniCMS.
- [ ] Apibrezti pranesimu tipus: nauja zinute, forumo atsakymas, komentaras, paminejimas, moderavimo ivykis, sistemos pranesimas.
- [ ] Prideti pranesimu statusus: unread, read, archived, dismissed.
- [ ] Prideti pranesimu nuostatas pagal tipa ir kanala.
- [ ] Apibrezti `in-app`, `email` ir ateities `digest` pristatymo kanalus.
- [ ] Isgryninti bendra `activity feed` varikli visam MiniCMS.
- [ ] Apibrezti activity feed ivykio modeli: actor, action, target, context, visibility.
- [ ] Nuspresti, kurie ivykiai eina tik i activity feed, o kurie ir i notification centra.
- [ ] Prideti activity feed filtrus: mano veikla, mano turinys, sekami objektai, sistemos veikla.
- [ ] Perasyti profilio logika: kontaktai, parasas, privatumo nustatymai, activity feed, statistics.
- [ ] Paruosti `security` skirtuko logika: slaptazodis, sesijos, 2FA.

### Branduolys ir sistemines paslaugos
- [ ] Ivesti `system settings registry`.
- [ ] Ivesti `feature flags`.
- [ ] Prideti `maintenance scheduler`.
- [ ] Prideti `plugin conflict detector`.
- [ ] Prideti centralizuota `error handler`.
- [ ] Prideti `request logger`.
- [ ] Paruosti `task scheduler / cron registry`.
- [ ] Prideti `search index abstraction`.
- [ ] Prideti vieninga paieskos saltiniu registravimo modeli branduoliui ir moduliams.
- [ ] Prideti `content revision history`.
- [ ] Prideti `draft / preview engine`.
- [ ] Prideti `trash / recycle bin`.
- [ ] Paruosti `hook debugger` ir `dependency graph` duomenu sluoksni.

### Saugumas
- [ ] Toliau mazinti hardcoded tekstus helperiuose ir baigti locale perkelima.
- [ ] Prideti daugiau smoke test helperiu svarbiausiems srautams.
- [ ] Ivesti vieninga cache sluoksni su `APCu` arba failu fallback.
- [ ] Ivesti `security headers manager`.
- [ ] Prideti `login alerts`.
- [ ] Prideti `suspicious activity detector`.
- [ ] Prideti `password reset audit`.
- [ ] Prideti `upload quarantine`.
- [ ] Prideti `read-only maintenance mode` logika.

### Funkcionalumas
- [ ] Isgryninti bendra paieskos varikli visam MiniCMS.
- [ ] Apibrezti paieskos saltinius: naujienos, forumas, naudotojai, komentarai, navigacija, paneles ir moduliai.
- [ ] Prideti paieskos rezultatu skaidyma pagal sekcija ir tipa.
- [ ] Prideti filtrus pagal moduli, data, autoriu, kategorija, taga ir role.
- [ ] Prideti relevancijos ir svoriu logika skirtingiems saltiniams.
- [ ] Prideti teisiu filtra, kad paieska nerodytu privataus ar neleistino turinio.
- [ ] Prideti `highlight` ir isskirto raktazodzio zymejimo logika rezultatuose.
- [ ] Prideti paieskos pasiulymus (`autocomplete`) ir populiariu paiesku logika.
- [ ] Prideti typo/synonym paieskos sluoksni, jei to reikes.
- [ ] Prideti paieskos analitika: dazniausios uzklausos, tuscios paieskos, neveikiantys raktazodziai.
- [ ] Prideti perindeksavimo (`reindex`) ir paieskos cache valymo logika.
- [ ] Prideti paieskos puslapiavimo, limito ir rusiavimo nustatymus.
- [ ] Prideti admin paieskos diagnostika: kas indeksuojama, kada paskutini karta atnaujinta, kiek irasu.
- [ ] Isgryninti bendra media/embed varikli visam MiniCMS.
- [ ] Apibrezti saugiu media saltiniu politika: vietiniai paveiksliukai, YouTube ir kiti leistini saltiniai.
- [ ] Prideti YouTube URL validacija, normalizavima ir embed generavima.
- [ ] Prideti paveiksliuku upload taisykles: limitai, MIME, matmenys, kvotos.
- [ ] Prideti bendra `embed renderer` sluoksni forumui, naujienoms ir kitiems moduliams.
- [ ] Isgryninti bendra `comments engine`, kad ji naudotu profiliai, naujienos ir kiti moduliai.
- [ ] Apibrezti vieninga komentaru taikinio modeli: naujienos, profiliai, puslapiai, paneles ar kiti moduliai.
- [ ] Nuspresti, kur reikia `flat` komentaru, o kur `threaded replies`.
- [ ] Prideti komentaru statusus: `pending`, `approved`, `hidden`, `spam`, `deleted`.
- [ ] Prideti komentaru moderavimo veiksmus: approve, hide, soft delete, restore, mark as spam.
- [ ] Prideti komentaru redagavimo langa, `edited at` ir `edited reason`.
- [ ] Prideti komentaru `quote / mention / reaction / report` galimybes.
- [ ] Prideti komentaru rusiavima: naujausi, seniausi, populiariausi.
- [ ] Prideti komentaru puslapiavima ir nustatyma, kiek komentaru rodyti.
- [ ] Prideti komentaru `flood control`, rate limits ir anti-spam tikrinimus.
- [ ] Prideti komentaru prenumeratas ir pranesimu centro ivykius.
- [ ] Prideti komentaru autoriaus role/badge/status rodyma prie komentaro korteles.
- [ ] Prideti komentaru paieskos ir administracinio filtravimo sluoksni.
- [ ] Paruosti bendra komentaru rendereri su BBCode / WYSIWYG / escaping taisyklemis.
- [ ] Paruosti galimybe modulio lygiu ijungti/isjungti komentarus ir pasirinkti komentaru taisykles.
- [ ] Prideti bendra `reactions` varikli.
- [ ] Prideti `bookmarks / favorites`.
- [ ] Prideti `reporting system`.
- [ ] Prideti `moderation queue` duomenu sluoksni.
- [ ] Prideti `user reputation`.
- [ ] Prideti `badges / achievements`.
- [ ] Prideti `polls` pagrindini duomenu modeli.

### Optimizavimas
- [ ] Prideti `page cache`.
- [ ] Prideti `panel cache`.
- [ ] Prideti `query profiling`.
- [ ] Prideti paieskos uzklausu profiliavima ir letos paieskos diagnostika.
- [ ] Prideti `asset bundling / minify` strategija.
- [ ] Prideti `image optimization`.
- [ ] Plesti `lazy loading` ir `cache warmup` logika.
