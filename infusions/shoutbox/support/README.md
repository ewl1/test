# Shoutbox support

`support/` katalogas skirtas procedurinei shoutbox logikai, kuri dar reikalinga pereinamuoju laikotarpiu.

## Taisykle
- `bootstrap.php`, `admin.php` ir `panel.php` turi likti ploni entrypoint failai.
- Proceduriniai helperiai skaidomi i mazesnius failus pagal atsakomybe.
- Tik tikri servisai, presenteriai ir kiti pakartotinai naudojami objektai keliauja i `classes/`.

## Dabartinis skaidymas
- `core.php`: baziniai nustatymai, mygtukai, limitai ir paprasti helperiai
- `mentions.php`: `@mention` logika ir vartotoju katalogas
- `messages.php`: zinuciu gavimas, kurimas, trynimas ir formatavimas
- `request.php`: POST srautas ir redirect logika
- `views.php`: editoriaus ir pilno shoutbox puslapio renderinimas
- `admin.php`: shoutbox administravimo helperiai ir vaizdas

## Kryptis
- Naujos sunkesnes verslo taisykles pirmiausia vertinamos `classes/` sluoksniui.
- `support/` skirtas suderinamumui ir laipsniskam perejimui nuo legacy funkciju prie modulio klasiu.
