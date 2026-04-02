<?php
if (!defined('DB_DOWNLOADS')) {
    define('DB_DOWNLOADS', 'infusion_downloads');
}
if (!defined('DB_DOWNLOAD_CATS')) {
    define('DB_DOWNLOAD_CATS', 'infusion_download_cats');
}

function downloads_upload_dir(): string
{
    return BASEDIR . 'uploads/downloads/';
}

function downloads_thumbs_dir(): string
{
    return BASEDIR . 'uploads/downloads/thumbs/';
}

function downloads_thumb_url(string $thumbnail): string
{
    if ($thumbnail === '') {
        return '';
    }
    return public_path('uploads/downloads/thumbs/' . rawurlencode($thumbnail));
}

/**
 * Grąžina FA7 ikonos klasę pagal failo plėtinį.
 * Grąžina masyvą ['icon' => 'fa-solid fa-...', 'color' => 'text-...']
 */
function downloads_file_icon(string $ext): array
{
    return match (strtolower($ext)) {
        'pdf'                   => ['icon' => 'fa-solid fa-file-pdf',     'color' => 'text-danger'],
        'zip', 'rar', '7z',
        'tar', 'gz', 'bz2'     => ['icon' => 'fa-solid fa-file-zipper',  'color' => 'text-warning'],
        'exe', 'msi'            => ['icon' => 'fa-solid fa-file-shield',  'color' => 'text-secondary'],
        'dmg', 'pkg'            => ['icon' => 'fa-brands fa-apple',       'color' => 'text-secondary'],
        'deb', 'rpm'            => ['icon' => 'fa-brands fa-linux',       'color' => 'text-secondary'],
        'apk'                   => ['icon' => 'fa-brands fa-android',     'color' => 'text-success'],
        'iso'                   => ['icon' => 'fa-solid fa-compact-disc', 'color' => 'text-info'],
        default                 => ['icon' => 'fa-solid fa-file',         'color' => 'text-secondary'],
    };
}

/**
 * Gauti downloads modulio nustatymą
 */
function downloads_setting(string $key, mixed $default = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            return $default;
        }
        $settings = new \App\MiniCMS\ModuleSettings($pdo, 'downloads', [
            'max_file_size' => '52428800',
            'show_thumbnails' => '1',
        ]);
    }
    return $settings->get($key, $default);
}

/**
 * Išsaugoti downloads modulio nustatymą
 */
function downloads_set_setting(string $key, mixed $value): bool
{
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        return false;
    }
    $settings = new \App\MiniCMS\ModuleSettings($pdo, 'downloads');
    return $settings->set($key, $value);
}

