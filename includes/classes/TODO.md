# Classes TODO

## Paskirtis
- Vieta būsimoms branduolio klasėms ir PSR-4 stiliaus pagrindiniams komponentams.

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

## Planuojamos branduolio klasės
- `App\\Auth\\AuthService`: prisijungimas, rate limits, admin sesija.
- `App\\Mail\\Mailer`: PHPMailer adapteris ir siuntimo fallback.
- `App\\Cache\\CacheStore`: `APCu` / failų cache.

## Architektūros ribos
- Modulių klasės neturi gyventi branduolio `includes/classes/` kataloge.
- `App\\Forum\\*` klasės perkeltos į `infusions/forum/classes/`.

## Likę darbai
- [~] Nuspręsti galutinį namespace ir autoload struktūrą tarp branduolio ir modulių.
- [x] Paruošta `composer.json` autoload sekcija (`psr-4`) su `App\\`.
- [x] `App\\Forum\\ForumService` iškeltas iš branduolio į forumo modulį.
- [ ] Toliau pradėti nuo `App\\Auth\\AuthService` ir `App\\Mail\\Mailer` kaip pirmų branduolio paslaugų be pilno projekto perrašymo.
