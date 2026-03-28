# Shoutbox TODO

## Paskirtis
- Saugus ir greitas shoutbox modulis su forma, BBCode, smailais, puslapiavimu ir moderavimu.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: flood control, teises ir patikimumas.
- `v1.3`: papildomas bendruomenes sluoksnis ir UX.
- `v1.4`: jei prireiks, isskaidyti logika i siauresnes klases.

## Svarbus failai
- `bootstrap.php`: zinuciu logika, renderinimas ir POST srautas.
- `admin.php`: moderavimo UI ir nustatymai.
- `panel.php`: shoutbox panele pagrindiniame puslapyje.
- `assets/css/shoutbox.css`, `assets/js/shoutbox.js`: modulio assetai.
- `locale/lt.php`: modulio tekstai.

## Likusieji darbai
- [x] CSS perkeltas i `assets/css/shoutbox.css`.
- [x] JS perkeltas i `assets/js/shoutbox.js`.
- [x] Modulio tekstai perkelti i `locale/lt.php`.
- [x] Shoutbox nustatymai perkelti i modulio admin puslapi.
- [ ] Prideti flood protection nustatymus per administracija.
- [ ] Prideti moderavimo veiksmus viesame puslapyje pagal teises.
- [ ] Prideti blokavimo / mute integracija su naudotoju bendru nustatymu sluoksniu.
- [ ] Prideti pranesimu ivykius, jei atsiras mention ar atsakymo logika.
- [ ] Apsvarstyti lengva auto-refresh arba dalini realtime atnaujinima.
- [ ] Paruosti shoutbox kompaktini ir pilna rodymo varianta su aiskesnemis zinuciu grupemis.
- [ ] Sutvarkyti mini avataru, laiko, autoriaus ir moderavimo veiksmu vizualine hierarchija.
- [ ] Paruosti nuoseklu BBCode, smailu ir media fragmentu pateikima shoutbox kontekste.
