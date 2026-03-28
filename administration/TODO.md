# Administration TODO

## Paskirtis
- Administracijos puslapiai, lentelės, nustatymai ir moderavimo UI.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Svarbūs failai
- `login.php`: atskiras admin prisijungimas ir sesijos patikra.
- `index.php`: dashboard santrauka ir greitos nuorodos.
- `settings.php`, `users.php`, `roles.php`, `permissions.php`: pagrindinis valdymo branduolys.
- `diagnostics.php`, `audit-logs.php`, `error-logs.php`: diagnostika ir stebėsena.

## Likę darbai
- [ ] Užbaigti visų administracijos formų tekstų perkėlimą į locale raktus.
- [ ] Pridėti `Clear cache / Clear rate limits / Clear reset tokens` įrankius.
- [ ] Suvienodinti tuos pačius filtrus ir paiešką visose administracijos lentelėse.
- [ ] Užbaigti kontrasto auditą visoms mažiau naudojamoms kortelėms ir badge elementams.
