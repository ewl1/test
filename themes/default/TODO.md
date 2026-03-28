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
- [ ] Sukurti tipografijos skale antrastems, turiniui, lentelems, badge ir pagalbiniams tekstams.
- [ ] Suvienodinti mygtuku, badge, korteliu, lenteliu, formu ir pranesimu komponentu stiliu.
- [ ] Isgryninti bendras turinio stiliaus taisykles BBCode ir WYSIWYG isvedimui.

### Viesas UX
- [ ] Toliau tvarkyti likusius lietuvisku raidziu neatitikimus sablonuose.
- [ ] Perasyti `header` taip, kad paieska, nario meniu ir pranesimai turetu aiskias vietas.
- [ ] Perasyti `profile.php` UI: avataras, kontaktai, veiklos srautas, statistika, privatumas, saugumas.
- [ ] Prideti `unread` badge komponentus zinutems, forumui ir pranesimams.
- [ ] Sukurti aiskesnes tuscias busenas, sekmes/klaidu busenas ir moderavimo veiksmu vizualini nuosekluma.
- [ ] Paruosti pagrindinio puslapio `hero` / `featured` paneliu presetus.

### Moduliu vizualinis polish
- [ ] Paruosti naujienu korteles su `featured image`, tagais, autoriumi ir skirtingais `excerpt` variantais.
- [ ] Paruosti forumo temu saraso vizualine hierarchija: badge, neperskaityta busena, autoriaus blokas, paskutinio aktyvumo zona.
- [ ] Paruosti shoutbox kompaktini ir detalesni rodymo varianta.
- [ ] Sutvarkyti paveiksliuku, avataru ir YouTube embed stilius, kad jie deretu prie temos.

### Admin ir responsive kokybe
- [ ] Perziureti kontrasta maziau naudojamiems admin komponentams.
- [ ] Sutvarkyti mobiliu ekranu elgsena admin lentelese, formose ir paneliu isdestyme.
- [ ] Pagerinti `panels` drag-drop griztamaji rysi ir `infusions` veiksmu matomuma.
- [ ] Toliau isskaidyti papildomu moduliu stilius is bendro `style.css`.

### Prieinamumas ir performance
- [ ] Prideti aiskesnes `focus` busenas ir patikrinti klaviaturos navigacija.
- [ ] Prideti `responsive image` ir `lazy loading` stiliaus taisykles ten, kur jos priklausys nuo temos.
- [ ] Ilgainiui apsvarstyti temos nustatymus: `compact / wide`, akcento spalvos, alternatyvus kontrasto rezimas.
