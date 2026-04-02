# Atsisiuntimai migrations

## Naming
- `001_1.0.1.php`
- `001_1.0.1.rollback.php`

## Flow
- Core pirmiausia iesko vykdytinu zingsniu siame kataloge.
- Jei nauju zingsniu nera, gali buti naudojamas `upgrade.php` fallback failas.
- Install / upgrade / uninstall yra apsaugoti bendru DB lock mechanizmu.
- Migraciju ir rollback istorija rodoma per `administration/infusions.php`.