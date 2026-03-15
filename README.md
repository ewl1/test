# Mini CMS Pro Scaffold

Tai funkcionalus **procedūrinis PHP Mini CMS** karkasas su:

- PHP + PDO
- `password_hash()` / `password_verify()`
- registracija / login / logout
- el. pašto aktyvacija su tokenais
- slaptažodžio keitimas / reset
- rolės ir leidimai iš DB
- admin dashboard
- narių valdymas
- audit log
- IP ban (IPv4 / IPv6) per `VARBINARY(16)`
- rate limit į DB
- shoutbox + moderavimas
- BBCode + ribotas HTML
- avatar upload
- Bootstrap 5.3 + jQuery + Font Awesome

## Svarbu

Ši versija yra **veikiantis karkasas / starteris**. Daug bazinių srautų yra realizuota, bet prieš production naudojimą dar verta:
- susitvarkyti SMTP `config.php`
- peržiūrėti validacijas
- pridėti `.htaccess`
- pasidaryti `install.php`
- sustiprinti upload resize / image processing
- pilnai padengti visas admin formas su papildomu UX

## Diegimas

1. Sukurti DB ir importuoti `database.sql`
2. Nukopijuoti `config.sample.php` į `config.php`
3. Susivesti DB ir SMTP parametrus
4. Paleisti:
   ```bash
   composer require phpmailer/phpmailer
   ```
5. Prisijungti su demo:
   - email: `admin@example.com`
   - slaptažodis: `password123`

## Struktūra

- `/include/` – bootstrap, auth, security, permissions, mail, helpers
- `/include/functions/` – CRUD ir pagalbinės funkcijos
- `/admin/` – administracija
- `/theme/` – `header.php`, `footer.php`
- `/uploads/avatars/` – avatarai
- `/logs/` – error log
