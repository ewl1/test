# Shoutbox TODO

## Paskirtis
- Šaukyklos modulis su forma, BBCode, smailais, puslapiavimu ir moderavimu.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Svarbūs failai
- `bootstrap.php`: žinučių logika, renderinimas ir POST srautas.
- `admin.php`: moderavimo UI.
- `panel.php`: šaukyklos panelė pagrindiniame puslapyje.
- `assets/css/shoutbox.css`, `assets/js/shoutbox.js`: modulio assetai.
- `locale/lt.php`: modulio tekstai.

## Likę darbai
- [x] CSS perkeltas į `assets/css/shoutbox.css`.
- [x] JS perkeltas į `assets/js/shoutbox.js`.
- [x] Modulio tekstai perkelti į `locale/lt.php`.
- [x] Šaukyklos nustatymai perkelti į modulio admin puslapį.
- [ ] Pridėti flood protection nustatymus per administraciją.
- [ ] Pridėti moderavimo veiksmus viešame puslapyje pagal teises.
- [ ] Apsvarstyti realaus laiko atnaujinimą be pilno perkrovimo.
