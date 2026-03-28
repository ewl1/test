<?php

namespace App\MiniCMS\Infusions;

use RuntimeException;

final class ModuleScaffolder
{
    public static function scaffold(string $projectRoot, string $folder, ?string $name = null, ?string $description = null): array
    {
        $folder = trim($folder);
        if ($folder === '') {
            throw new RuntimeException('Modulio katalogas negali buti tuscias.');
        }

        $studly = InfusionManifest::studly($folder);
        if ($studly === '') {
            throw new RuntimeException('Nepavyko sugeneruoti modulio namespace is katalogo pavadinimo.');
        }

        $moduleRoot = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'infusions' . DIRECTORY_SEPARATOR . $folder;
        if (is_dir($moduleRoot)) {
            throw new RuntimeException('Toks modulio katalogas jau egzistuoja: ' . $folder);
        }

        $name = trim((string)($name ?? ucwords(str_replace(['-', '_'], ' ', $folder))));
        $description = trim((string)($description ?? ($name . ' SDK modulis.')));
        $moduleClass = $studly . 'Module';
        $namespace = 'App\\' . $studly;

        $files = [
            $moduleRoot . '/manifest.json' => self::manifestTemplate($folder, $name, $description, $namespace . '\\' . $moduleClass),
            $moduleRoot . '/classes/' . $moduleClass . '.php' => self::moduleClassTemplate($namespace, $moduleClass),
            $moduleRoot . '/panel.php' => self::panelTemplate($name),
            $moduleRoot . '/admin.php' => self::adminTemplate($name, $folder),
            $moduleRoot . '/schema.php' => self::schemaTemplate($folder),
            $moduleRoot . '/uninstall.php' => self::uninstallTemplate($folder),
            $moduleRoot . '/migrations/.gitkeep' => '',
            $moduleRoot . '/locale/lt.php' => self::localeTemplate($folder, $name),
            $moduleRoot . '/assets/css/' . $folder . '.css' => self::cssTemplate($folder),
            $moduleRoot . '/assets/js/' . $folder . '.js' => self::jsTemplate($folder),
            $moduleRoot . '/README.md' => self::readmeTemplate($folder, $name),
        ];

        foreach ($files as $path => $contents) {
            $dir = dirname($path);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Nepavyko sukurti katalogo: ' . $dir);
            }
            file_put_contents($path, $contents);
        }

        return array_keys($files);
    }

    private static function manifestTemplate(string $folder, string $name, string $description, string $moduleClass): string
    {
        $manifest = [
            'name' => $name,
            'description' => $description,
            'version' => '1.0.0',
            'author' => 'MiniCMS SDK',
            'admin' => true,
            'bootstrap' => false,
            'panel' => true,
            'schema' => true,
            'upgrade' => false,
            'default_position' => 'right',
            'default_panel_name' => $name,
            'min_core_version' => '1.0.0',
            'module_class' => $moduleClass,
            'dependencies' => [],
            'permissions' => [
                [
                    'name' => $name . ' administravimas',
                    'slug' => $folder . '.admin',
                    'description' => 'Leidzia valdyti ' . mb_strtolower($name),
                ],
            ],
            'admin_menu' => [
                [
                    'title' => $name,
                    'slug' => $folder . '-admin',
                    'permission' => $folder . '.admin',
                    'sort_order' => 200,
                ],
            ],
        ];

        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    private static function moduleClassTemplate(string $namespace, string $moduleClass): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use App\MiniCMS\Infusions\AbstractInfusionModule;

final class {$moduleClass} extends AbstractInfusionModule
{
    public function boot(): void
    {
        \$this->registerStyle('assets/css/' . \$this->context->folder() . '.css');
        \$this->registerScript('assets/js/' . \$this->context->folder() . '.js');
    }
}
PHP;
    }

    private static function panelTemplate(string $name): string
    {
        return <<<PHP
<?php
openside('{$name}');
echo '<div class="sdk-module-panel" data-sdk-module="' . e(panel_render_current_panel()['folder'] ?? '') . '">';
echo '<div class="fw-semibold mb-2">' . e('{$name}') . '</div>';
echo '<div class="small text-secondary">SDK paneles turinys. Cia galite rodyti modulio santrauka.</div>';
echo '</div>';
closeside();
PHP;
    }

    private static function adminTemplate(string $name, string $folder): string
    {
        return <<<PHP
<?php
require_permission('{$folder}.admin');
?>
<div class="card">
    <div class="card-header">{$name} administravimas</div>
    <div class="card-body">
        <p class="mb-0 text-secondary">SDK administracijos placeholder. Cia dekite modulio nustatymus, CRUD ir diagnostika.</p>
    </div>
</div>
PHP;
    }

    private static function schemaTemplate(string $folder): string
    {
        return <<<PHP
<?php
\$GLOBALS['pdo']->exec("
CREATE TABLE IF NOT EXISTS infusion_{$folder}_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
PHP;
    }

    private static function uninstallTemplate(string $folder): string
    {
        return <<<PHP
<?php
\$GLOBALS['pdo']->exec("DROP TABLE IF EXISTS infusion_{$folder}_items");
PHP;
    }

    private static function localeTemplate(string $folder, string $name): string
    {
        return <<<PHP
<?php
return [
    '{$folder}.title' => '{$name}',
];
PHP;
    }

    private static function cssTemplate(string $folder): string
    {
        return <<<CSS
.sdk-module-panel {
    display: grid;
    gap: 0.5rem;
}
CSS;
    }

    private static function jsTemplate(string $folder): string
    {
        return <<<JS
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-sdk-module="{$folder}"]').forEach(function () {
        // Modulio JS vieta.
    });
});
JS;
    }

    private static function readmeTemplate(string $folder, string $name): string
    {
        return <<<MD
# {$name}

Sis modulis sugeneruotas per MiniCMS Module SDK scaffold.

## Failai
- `manifest.json`: modulio metaduomenys ir registracija
- `classes/`: modulio SDK klase
- `panel.php`: paneles turinys ir legacy `openside()/closeside()` apvalkalas
- `admin.php`: admin vaizdas
- `schema.php`: diegimo DB schema
- `migrations/`: versijiniai atnaujinimu ir rollback zingsniai
- `uninstall.php`: pasalinimo logika
- `locale/`: modulio tekstai
- `assets/`: modulio CSS ir JS
MD;
    }
}
