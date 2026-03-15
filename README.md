Mini CMS v0.0.12

| Komponentas | Minimalu | Rekomenduojama |
| ----------- | -------- | -------------- |
| **Apache**  | 2.4      | 2.4.57+        |
| **PHP**     | 8.1      | 8.2 / 8.3      |
| **MySQL**   | 5.7      | 8.0            |
| **MariaDB** | 10.4     | 10.6+          |

## Reikalingi PHP moduliai
 pdo
pdo_mysql
mbstring
openssl
json
fileinfo

## PHP nustatymai
upload_max_filesize = 5M
post_max_size = 6M
memory_limit = 128M
max_execution_time = 30

## MySQL / MariaDB nustatymai
utf8mb4
utf8mb4_unicode_ci

## Serverio tipas
Apache + mod_rewrite
Nginx + PHP-FPM

## Optimizacija avatarams
max size: 2MB
format:
jpg
png
webp
resize iki: 256x256
