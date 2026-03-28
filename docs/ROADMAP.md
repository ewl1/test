# MiniCMS Roadmap

## Paskirtis
- Sis dokumentas nusako artimiausius MiniCMS etapus, kad darbai butu daromi nuosekliai, o ne padrikai.
- Katalogu `TODO.md` failai lieka detalus darbiniu uzduociu sarasas.
- Sis roadmap nurodo prioritetus ir versiju krypti.

## Kaip naudoti
- `v1.1` skirtas stabilumui, saugumui ir administracijos uzbaigimui.
- `v1.2` skirtas branduolio architekturai ir OOP/PSR-4 perejimui be pilno perrasymo.
- `v1.3` skirtas SEO, UX, moderatoriu ir bendruomenes funkciju augimui.
- Nauja uzduotis turi buti priskirta bent vienam etapui ir tada irasyta i atitinkamo katalogo `TODO.md`.

## v1.1 Stabilumas ir saugumas

### Tikslas
- Uzbaigti tai, kas tiesiogiai itakoja sauguma, administracijos patikimuma, lokalizacija ir kasdienini naudojima.

### Prioritetai
- Pabaigti locale perkelima visiems antriniams admin ir paieskos puslapiams.
- Prideti `session_regenerate_id()` po prisijungimo ir po admin prisijungimo.
- Jautriems admin veiksmams numatyti papildoma admin slaptazodzio patvirtinima.
- Prideti `Clear cache / Clear rate limits / Clear reset tokens` irankius administracijoje.
- Diagnostikoje aiskiai suskirstyti pletinius i `butini`, `rekomenduojami`, `pasirenkami`.
- Uzbaigti kontrasto ir lietuvisku raidziu audita visiems admin puslapiams.
- Shoutbox ir forumui prideti flood/spam apsauga.

### Baigtumo kriterijai
- Nebelieka likusiu admin puslapiu su `?` vietoj lietuvisku raidziu.
- Admin lenteles turi vieninga kontrasta, filtrus ir paieskos elgsena.
- Prisijungimo, reset ir admin sesiju srautai turi papildoma apsauga.
- Diagnostikos puslapis aiskiai paaiskina, ko projektui tikrai reikia.

## v1.2 Architektura ir branduolio stiprinimas

### Tikslas
- Mazinti proceduralinio kodo apkrova ten, kur tai jau pradeda trukdyti prieziurai, bet neperrasyti visko vienu kartu.

### Prioritetai
- Pilnai pradeti branduolio klases:
- `App\Auth\AuthService`
- `App\Mail\Mailer`
- `App\Cache\CacheStore`
- Isigryninti, kas lieka helperiuose, o kas keliauja i servisus.
- Padaryti vieninga cache sluoksni su `APCu` ir failu fallback.
- Sutvarkyti Composer `autoload` ir namespace ribas tarp branduolio ir infusion moduliu.
- Toliau skaidyti forumo logika is `bootstrap.php` i modulio klases.
- Paruosti bendras smoke-test helperiu vietas svarbiausiems srautams.

### Baigtumo kriterijai
- Auth, Mail ir Cache turi aiskius servisus su minimaliu adapterio sluoksniu.
- Moduliu klases nebegyvena `includes/classes/`.
- Nauja funkcija pirmiausia kuriama per servisus arba aiskias modulio klases, o ne dar viena dideli helperi.

## v1.3 SEO, UX ir augimas

### Tikslas
- Po stabilumo ir branduolio darbu pereiti prie matomumo, naudotojo patirties ir bendruomenes funkciju.

### Prioritetai
- Prideti `sitemap.xml`, `canonical`, `meta description`, `Open Graph`, `schema.org`.
- Jei atsiras kelios kalbos, prideti `hreflang`.
- Forume prideti `edited by`, moderavimo istorija, report sistema, neperskaitytu temu logika.
- Shoutbox apsvarstyti viesus moderavimo veiksmus pagal teises ir lengva auto-refresh.
- Panelems prideti matomuma pagal role ir puslapi, o taip pat paneliu cache.
- Prideti pranesimu centra, privataus bendravimo ar sekanciu temu/favoritu logika.
- Toliau poliruoti viesaji ir admin dizaina: tuscios busenos, badge, spacing, tipografija, mobile.

### Baigtumo kriterijai
- Projektas turi bazini SEO sluoksni.
- Moderatoriai turi pilnesne veiksmu istorija.
- Naudotojai gauna daugiau bendruomenes ir navigacijos funkciju be dizaino regressiju.

## Ne pirmo prioriteto darbai
- Geo funkcijos vertingos tik jei projektas tures vietoviu ar lokalios imones turini.
- `php-class-diagram` verta jungti tada, kai branduolyje ir moduliuose atsiras daugiau realiu klasiu.
- Pilnas perrasymas i OOP dabar nera tikslas.

## Rekomenduojama vykdymo seka
1. Uzbaigti `v1.1`.
2. Iskart po to pradeti `AuthService`, `Mailer`, `CacheStore`.
3. Tik tada plesti SEO, forumo moderavimo ir UX funkcijas.
