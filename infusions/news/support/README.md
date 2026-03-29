# News support

`support/` katalogas skirtas procedurinei naujienu modulio logikai, kuri dar neiskelta i tikras SDK klases.

## Taisykle
- `admin.php` ir `panel.php` turi likti ploni entrypoint failai.
- Proceduriniai helperiai skaidomi i mazesnius failus pagal atsakomybe.
- Jei logika tampa pakartotinai naudojamu servisu ar presenteriu, ji keliama i `classes/`.

## Dabartinis skaidymas
- `helpers.php`: lenteles, slug ir duomenu nuskaitymo helperiai
- `admin.php`: administravimo POST srautas ir vaizdas
- `panel.php`: paneles renderinimas

## Kryptis
- Kai modulis gaus pilna publikavimo workflow, verta pradeti kelti logika i `NewsService`, `PublicationService` ir kitus klasiu sluoksnius.
