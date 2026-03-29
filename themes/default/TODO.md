# Theme TODO

## Paskirtis
- Numatytoji viesoji ir administracijos tema, bendra vizualine kalba ir layout komponentai.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: kontrastas, lietuviskos raides, svarbiausi UI neatitikimai.
- `v1.2`: profilio, zinuciu, pranesimu centro UI ir pirmas design system sluoksnis.
- `v1.3`: forumo, naujienu, shoutbox ir bendruomenes komponentu polish.
- `v1.4`: bendru komponentu isgryninimas ir temos nustatymu kryptis.
- `v1.5`: SEO renderio, performance ir prieinamumo UI sluoksnis.

## Svarbus failai
- `header.php`, `footer.php`: viesas karkasas ir globalus assetai.
- `admin_header.php`, `admin_footer.php`: administracijos karkasas.
- `style.css`, `admin.css`: bendri stiliai.

## Likusieji darbai

### Design system
- [ ] Apibrezti bendrus `design token` saltinius: spalvos, tarpai, radius, seseliai, ikonografija, `z-index`.
- [ ] Islaikyti tema kaip vieninteli bendra `:root` tokenu saltini, kuri moduliu CSS vartoja, o ne perraso globaliai.
- [ ] Apibrezti `layout system` pagrinda: konteineriai, `content/sidebar/full-width`, tarpai tarp sekciju ir bendros grid taisykles.
- [ ] Paruosti bazinius puslapiu layout sablonus: `home`, vidinis turinys, forumas, naujiena, profilis.
- [ ] Sukurti tipografijos skale antrastems, turiniui, lentelems, badge ir pagalbiniams tekstams.
- [ ] Suvienodinti mygtuku, badge, korteliu, lenteliu, formu ir pranesimu komponentu stiliu.
- [ ] Isgryninti bendras turinio stiliaus taisykles BBCode ir WYSIWYG isvedimui.
- [x] Apsispresti del pagrindines ikon bibliotekos:
- pagrindine bendra biblioteka yra `Font Awesome 7`
- antrine biblioteka leidziama tik jei `FA7` neturi konkretaus rinkinio ir yra aiski produkto priezastis
- `Entypo` nelaikyti lygiaverciu treciu pagrindu be aiskaus poreikio
- [ ] Susideti ikon zemelapi navigacijai, statusams, veiksmams, tuscioms busenoms ir moderavimui.

### Viesas UX
- [ ] Toliau tvarkyti likusius lietuvisku raidziu neatitikimus sablonuose.
- [ ] Perasyti `header` taip, kad paieska, nario meniu ir pranesimai turetu aiskias vietas.
- [ ] Perasyti `profile.php` UI: avataras, kontaktai, veiklos srautas, statistika, privatumas, saugumas.
- [ ] Prideti `unread` badge komponentus zinutems, forumui ir pranesimams.
- [ ] Sukurti aiskesnes tuscias busenas, sekmes/klaidu busenas ir moderavimo veiksmu vizualini nuosekluma.
- [ ] Paruosti pagrindinio puslapio `hero` / `featured` paneliu presetus.
- [ ] Paruosti mobilu `header` ir `member menu` scenariju su patogia paieska ir greitais veiksmais.
- [ ] Apsvarstyti `sticky` greitu veiksmu juosta mobiliems ekranams ten, kur tai prasminga.

### Moduliu vizualinis polish
- [ ] Paruosti naujienu korteles su `featured image`, tagais, autoriumi ir skirtingais `excerpt` variantais.
- [ ] Paruosti forumo temu saraso vizualine hierarchija: badge, neperskaityta busena, autoriaus blokas, paskutinio aktyvumo zona.
- [ ] Paruosti shoutbox kompaktini ir detalesni rodymo varianta.
- [ ] Sutvarkyti paveiksliuku, avataru ir YouTube embed stilius, kad jie deretu prie temos.
- [ ] Prideti daugiau semantiniu ikonu moduliu veiksmams, badge ir tuscioms busenoms.

### Admin ir responsive kokybe
- [ ] Perziureti kontrasta maziau naudojamiems admin komponentams.
- [ ] Sutvarkyti mobiliu ekranu elgsena admin lentelese, formose ir paneliu isdestyme.
- [x] Pagerinti `panels` drag-drop griztamaji rysi ir `infusions` veiksmu matomuma.
- [x] Po ikonu ivedimo perbalansuoti `infusions` korteliu vizualini tanki: badge auksti, mygtuku ploti, eiluciu ritma ir tarpus tarp meta juostu.
- `administration/infusions.php` title stulpelyje palikta kompaktiska statusu juosta, o meta informacija pervesta i ritmingas `label/value` eilutes
- `themes/default/admin.css` sumazintas badge aukstis, veiksmu mygtuku plotis ir `infusions` korteliu tarpai
- [ ] Po galutinio `FA7` pajungimo atlikti `infusions` ekrano ikonografijos audita: mazinti pasikartojancias ikonas ir palikti tik tas, kurios duoda papildoma prasme, o ne dubliuoja teksta.
- [ ] `Danger` veiksmus administracijoje vizualiai atskirti nuo neutraliu: ne tik spalva, bet ir vieta, kad `disable / uninstall` nebutu sumaisyti su `admin / settings / health`.
- [ ] `Infusions` kortelese suvienodinti viena statuso juosta ir viena veiksmu juosta, o perteklines technines badge juostas perkelti i detalu rodini.
- [ ] Toliau isskaidyti papildomu moduliu stilius is bendro `style.css`.
- [ ] Apibrezti breakpoint'us viesai ir admin daliai, kad elgsena butu prognozuojama, o ne atsitiktine.
- [x] Paruosti admin layout sablonus: `dashboard`, `table view`, `form view`, `split settings` ir `diagnostics`.
- bendra admin layout baze dabar yra `themes/default/admin_layout.php` ir `docs/ADMIN-LAYOUTS.md`
- `dashboard` ir `diagnostics` jau pervesti ant naujo helperio bei preset klasiu
- `table view`, `form view` ir `split settings` presetai paruosti kaip tema/helperio standartas tolimesniam perkelimui
- [ ] Admin lentelese parengti `table-to-card` arba horizontal scroll strategija maziems ekranams.

### Ikonografija ir prieinamumas
- [ ] Jei bus naudojamos ikon-only mygtuku versijos, prideti `aria-label`, `title` ir `tooltip` taisykles.
- [ ] Apsvarstyti bendra CSS klase arba helperi ikonoms, kad skirtingi rinkiniai butu kvieciami vienodu budu.
- [ ] Nepalikti svarbiu veiksmu tik ant ikonos be teksto, ypac administracijoje.

### Prieinamumas ir performance
- [ ] Prideti aiskesnes `focus` busenas ir patikrinti klaviaturos navigacija.
- [ ] Prideti `responsive image` ir `lazy loading` stiliaus taisykles ten, kur jos priklausys nuo temos.
- [ ] Ilgainiui apsvarstyti temos nustatymus: `compact / wide`, akcento spalvos, alternatyvus kontrasto rezimas.

