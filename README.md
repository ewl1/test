# Mini CMS 

1. Minimalios serverio versijos

Komponentas	Minimalu	Rekomenduojama
Apache	2.4	2.4.57+
PHP	8.1	8.2 / 8.3
MySQL	5.7	8.0
MariaDB	10.4	10.6+

2. Reikalingi PHP moduliai

pdo
pdo_mysql
mbstring
openssl
json
fileinfo
gd (avatar resize jei pridėsi)

3. Minimalūs PHP nustatymai

upload_max_filesize = 5M
post_max_size = 6M
memory_limit = 128M
max_execution_time = 30

4. MySQL / MariaDB nustatymai

Rekomenduojama charset:

utf8mb4
utf8mb4_unicode_ci

5. Serverio tipas

Apache + mod_rewrite
Nginx + PHP-FPM

6. Maža optimizacija avatarams

max size: 2MB
format:
jpg
png
webp

ir resize iki: 256x256

su PHP GD.
