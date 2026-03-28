# Classes TODO

## Paskirtis
- Vieta būsimoms klasėms ir PSR-4 stiliaus branduolio komponentams.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Planuojamos klasės
- `App\\Auth\\AuthService`: prisijungimas, rate limits, admin sesija.
- `App\\Mail\\Mailer`: PHPMailer adapteris ir siuntimo fallback.
- `App\\Cache\\CacheStore`: `APCu` / failų cache.
- `App\\Forum\\ForumService`: forumo temos, atsakymai, moderavimas.

## Likę darbai
- [ ] Nuspręsti galutinį namespace ir autoload struktūrą.
- [x] Paruošta `composer.json` autoload sekcija (`psr-4`) su `App\\`.
- [x] Pridėti pradiniai karkasiniai servisai `AuthService`, `Mailer`, `ForumService`.
- [ ] Pradėti nuo `App\\Auth\\AuthService`, `App\\Mail\\Mailer` ir `App\\Forum\\ForumService` kaip pirmų paslaugų be pilno projekto perrašymo.
