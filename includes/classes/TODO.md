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
- [ ] Paruošti `composer.json` autoload sekciją (`psr-4`).
- [ ] Išskaidyti pirmas 1-2 paslaugas be pilno projekto perrašymo.
