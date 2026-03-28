# Infusions TODO

## Paskirtis
- Vieta bendrai modulių architektūrai, asset strategijai ir locale taisyklėms.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Etapai
- `v1.1`: suvienodinti locale ir asset struktura visiems esamiems moduliams.
- `v1.2`: daugiau moduliniu klasiu ir aiskesnes ribos su branduoliu.
- `v1.3`: modulio kokybes sluoksniai, papildomi assetai ir moderavimo/UX plėtra.

## Taisyklės
- Modulio specifiniai tekstai laikomi `infusions/<modulis>/locale/`.
- Modulio specifiniai CSS ir JS laikomi `infusions/<modulis>/assets/`.
- Bendri paveikslėliai ir avatarai laikomi `/images/`, nebent assetas griežtai tik vidinis moduliui.

## Likę darbai
- [ ] Tą pačią asset/locale struktūrą pritaikyti ir kitiems infusion moduliams.
- [ ] Sugalvoti vienodą manifest ir admin UI lokalizavimo strategiją.
