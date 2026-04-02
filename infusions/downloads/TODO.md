# Downloads Module TODO

## Formatas
- `[ ]` laukia
- `[~]` vyksta
- `[x]` padaryta

---

## v1.0 — Pagrindai (perrašyta iš naujo)

- [x] DB schema (`infusion_downloads`, `infusion_download_cats`)
- [x] `bootstrap.php` — `DB_DOWNLOADS`, `DB_DOWNLOAD_CATS` konstantos + `downloads_upload_dir()` funkcija
- [x] `downloads.php` — naudoja `includes/bootstrap.php`, `require_permission()`, `e()`, `format_bytes_human()`, `public_path()`
- [x] `admin.php` — `require_permission('downloads.admin')`, `verify_csrf()`, POST-only delete, `audit_log()`, failų tipų whitelist, atskira `/uploads/downloads/` direktorija su `.htaccess`
- [x] `panel.php` — `openside()`/`closeside()` iš `includes/functions/output.php`
- [x] Pašalintos Gemini sukurtos klaidos: `i_have_access()`, `i_get_user_id()`, `DB_USERS`, `FUSION_SELF`, `$_SERVER['PHP_SELF']`, `format_filesize()`, `/images/avatars/`

---

## v1.1 — Funkcionalumo plėtra

- [x] Išorinės atsisiuntimo nuorodos (laukas `download_url` — admin forma, frontend, redirect dispatch)
- [x] Integruoti su bendra paieška (`ModuleSearchContract`) — ieškoti pagal `download_title`, `download_description`
- [ ] Atsisiuntimų statistika admin: populiariausi, naujausiai įkelti, iš viso failų/dydžio
- [ ] Skaičiuoti atsisiuntimus tik vieną kartą per sesiją (dabar kiekvienas request skaičiuojamas)
- [ ] Moderavimo eilė naujai įkeltiems failams (`download_status` kolona schemoje)

---

## v1.2 — UI/UX ir lokalizacija

- [x] Sukurti `locale/lt.php` su visais UI raktais
- [x] `downloads.php` — pilnai naudoja `__()`
- [x] `admin.php` TAB 1 (kategorijos) — naudoja `__()`
- [x] `admin.php` TAB 2 (atsisiuntimai) — pakeisti hardcoded tekstus į `__()`
- [x] `admin.php` TAB 3 (nustatymai) — pakeisti hardcoded tekstus į `__()`
- [x] `locale/lt.php` placeholder sintaksė: `{key}` → `:key` (suderinta su `__()` funkcija)
- [x] Failo tipo ikona pagal plėtinį (FA7)
- [x] Atnaujinti `manifest.json` `changelog` su `1.0.1` įrašu apie perrašymą
