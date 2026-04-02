# Atsisiuntimai support

`support/` katalogas skirtas procedurinei modulio logikai, kuri dar neiskelta i SDK klases.

## Taisykle
- `bootstrap.php`, `admin.php` ir `panel.php` turi likti ploni entrypoint failai.
- Proceduriniai helperiai skaidomi i mazesnius failus pagal atsakomybe.
- Tikri servisai, presenteriai ir objektai turi keliauti i `classes/`.
- Nedarykite vieno monolitinio `feature_pack.php` tipo failo, jei helperius galima isskaidyti i aiskius `support/` failus.