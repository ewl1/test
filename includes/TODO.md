# Includes TODO

## Paskirtis
- Bendri helperiai, saugumo sluoksnis, autentikacija, validacija, locale, paštas, profiliai ir pagalbinės funkcijos.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Svarbūs failai
- `bootstrap.php`: sesijos, saugumo antraštės, bendras įkrovimas.
- `locale.php`: locale failų užkrovimas branduoliui ir infusion moduliams.
- `auth.php`, `security.php`, `permissions.php`, `validation.php`: pagrindinis saugumo ir prisijungimo sluoksnis.
- `password_resets.php`, `mail.php`: slaptažodžio atstatymas ir el. laiškai.
- `user_profiles.php`: profilio reitingai, komentarai ir viešo profilio statistika.

## Funkcijos ir klasės
- Funkcinis stilius vis dar yra pagrindas.
- `includes/classes/` kol kas laikomas paruoštas būsimiems OOP komponentams.

## Likę darbai
- [ ] Toliau mažinti hardcoded tekstus helperiuose ir baigti locale perkėlimą.
- [ ] Pridėti daugiau smoke test helperių svarbiausiems srautams.
- [ ] Įvesti vieningą cache sluoksnį su `APCu` arba failų fallback.
- [ ] Išgryninti, kur verta pereiti prie OOP paslaugų (`Mail`, `Auth`, `Cache`, `Forum`).
