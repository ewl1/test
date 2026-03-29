# Forum support

`support/` katalogas skirtas procedurinei forumo logikai, kuri dar reikalinga pereinamuoju laikotarpiu.

## Taisykle
- `bootstrap.php`, `admin.php` ir `panel.php` turi likti ploni entrypoint failai.
- Proceduriniai helperiai skaidomi i mazesnius failus pagal atsakomybe.
- Tik tikri servisai, presenteriai ir kiti pakartotinai naudojami objektai keliauja i `classes/`.

## Dabartinis skaidymas
- `schema.php`: papildomos forumo lenteles ir schema
- `settings.php`: forumo nustatymai ir ju validacija
- `meta.php`: forumo meta, alias, paveiksliukai, raktažodziai
- `moods.php`: forumo nuotaikos
- `ranks.php`: forumo rangai
- `display.php`: bendri vizualiniai helperiai
- `topic_behavior.php`: reply/topic elgsena, dalyviai, popularumas
- `attachments.php`: priedu ikelimas ir atvaizdavimas
- `admin.php`: forumo admin veiksmu helperiai

## Kryptis
- Naujos sunkesnes verslo taisykles pirmiausia vertinamos `classes/` sluoksniui.
- `support/` skirtas suderinamumui ir laipsniskam perejimui nuo legacy funkciju prie modulio klasiu.
