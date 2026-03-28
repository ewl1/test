<?php

namespace App\MiniCMS\Installer;

use RuntimeException;

class ConfigWriter
{
    public function write(string $path, array $config): void
    {
        $content = $this->buildContent($this->normalize($config));
        $bytes = @file_put_contents($path, $content);

        if ($bytes === false) {
            throw new RuntimeException('Nepavyko irasyti config failo.');
        }
    }

    private function normalize(array $config): array
    {
        $normalized = [
            'app_name' => trim((string)($config['app_name'] ?? 'Mini CMS Pro')),
            'site_url' => rtrim(trim((string)($config['site_url'] ?? 'http://localhost/minicms')), '/'),
            'db_host' => trim((string)($config['db_host'] ?? 'localhost')),
            'db_name' => trim((string)($config['db_name'] ?? 'minicms')),
            'db_user' => trim((string)($config['db_user'] ?? 'root')),
            'db_pass' => (string)($config['db_pass'] ?? ''),
            'mail_host' => trim((string)($config['mail_host'] ?? 'smtp.example.com')),
            'mail_port' => max(1, (int)($config['mail_port'] ?? 587)),
            'mail_user' => trim((string)($config['mail_user'] ?? 'user@example.com')),
            'mail_pass' => (string)($config['mail_pass'] ?? ''),
            'mail_from' => trim((string)($config['mail_from'] ?? 'noreply@example.com')),
            'mail_from_name' => trim((string)($config['mail_from_name'] ?? 'Mini CMS Pro')),
            'current_theme' => trim((string)($config['current_theme'] ?? 'default')),
            'admin_theme' => trim((string)($config['admin_theme'] ?? 'default')),
            'timezone' => trim((string)($config['timezone'] ?? 'Europe/Vilnius')),
        ];

        foreach (['app_name', 'site_url', 'db_host', 'db_name', 'db_user', 'current_theme', 'admin_theme', 'timezone'] as $required) {
            if ($normalized[$required] === '') {
                throw new RuntimeException('Truksta laukelio: ' . $required);
            }
        }

        return $normalized;
    }

    private function buildContent(array $config): string
    {
        return "<?php\n"
            . "define('APP_NAME', " . var_export($config['app_name'], true) . ");\n"
            . "define('SITE_URL', " . var_export($config['site_url'], true) . ");\n"
            . "define('DB_HOST', " . var_export($config['db_host'], true) . ");\n"
            . "define('DB_NAME', " . var_export($config['db_name'], true) . ");\n"
            . "define('DB_USER', " . var_export($config['db_user'], true) . ");\n"
            . "define('DB_PASS', " . var_export($config['db_pass'], true) . ");\n"
            . "define('MAIL_HOST', " . var_export($config['mail_host'], true) . ");\n"
            . "define('MAIL_PORT', " . (int)$config['mail_port'] . ");\n"
            . "define('MAIL_USERNAME', " . var_export($config['mail_user'], true) . ");\n"
            . "define('MAIL_PASSWORD', " . var_export($config['mail_pass'], true) . ");\n"
            . "define('MAIL_FROM', " . var_export($config['mail_from'], true) . ");\n"
            . "define('MAIL_FROM_NAME', " . var_export($config['mail_from_name'], true) . ");\n"
            . "define('CURRENT_THEME', " . var_export($config['current_theme'], true) . ");\n"
            . "define('ADMIN_THEME', " . var_export($config['admin_theme'], true) . ");\n"
            . "define('TIMEZONE', " . var_export($config['timezone'], true) . ");\n"
            . "define('APP_VERSION', '1.0.0');\n"
            . "define('MAINTENANCE_MODE', false);\n";
    }
}
