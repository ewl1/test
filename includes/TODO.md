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
- [ ] Prideti `content revision history`.
- [ ] Prideti `draft / preview engine`.
- [ ] Prideti `trash / recycle bin`.
- [ ] Paruosti `hook debugger` ir `dependency graph` duomenu sluoksni.

### Saugumas
- [ ] Toliau mazinti hardcoded tekstus helperiuose ir baigti locale perkėlima.
- [ ] Prideti daugiau smoke test helperiu svarbiausiems srautams.
- [ ] Ivesti vieninga cache sluoksni su `APCu` arba failu fallback.
- [ ] Ivesti `security headers manager`.
- [ ] Prideti `login alerts`.
- [ ] Prideti `suspicious activity detector`.
- [ ] Prideti `password reset audit`.
- [ ] Prideti `upload quarantine`.
- [ ] Prideti `read-only maintenance mode` logika.

### Funkcionalumas
- [ ] Isgryninti bendra `comments engine`, kad ji naudotu profiliai, naujienos ir kiti moduliai.
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
- [ ] Prideti `asset bundling / minify` strategija.
- [ ] Prideti `image optimization`.
- [ ] Plesti `lazy loading` ir `cache warmup` logika.
