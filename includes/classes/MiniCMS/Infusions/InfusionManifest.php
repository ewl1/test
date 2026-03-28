<?php

namespace App\MiniCMS\Infusions;

use RuntimeException;

final class InfusionManifest
{
    private string $folder;
    private array $data;

    private function __construct(string $folder, array $data)
    {
        $this->folder = $folder;
        $this->data = $data;
    }

    public static function fromFile(string $folder): self
    {
        $path = INFUSIONS . trim($folder) . '/manifest.json';
        if (!is_file($path)) {
            throw new RuntimeException('Manifest nerastas: ' . $folder);
        }

        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data)) {
            throw new RuntimeException('Manifest JSON klaidingas: ' . $folder);
        }

        return self::fromArray($folder, $data);
    }

    public static function fromArray(string $folder, array $data): self
    {
        return new self(trim($folder), self::normalize(trim($folder), $data));
    }

    public static function studly(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', (string)$value) ?? '';
        $value = ucwords(strtolower(trim($value)));
        return str_replace(' ', '', $value);
    }

    public function folder(): string
    {
        return $this->folder;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function moduleClass(): ?string
    {
        $class = trim((string)($this->data['module_class'] ?? ''));
        return $class !== '' ? $class : null;
    }

    public function defaultModuleClass(): string
    {
        $studly = self::studly($this->folder);
        return 'App\\' . $studly . '\\' . $studly . 'Module';
    }

    private static function normalize(string $folder, array $data): array
    {
        $moduleClass = trim((string)($data['module_class'] ?? ($data['sdk']['module_class'] ?? '')));

        return [
            'folder' => $folder,
            'name' => trim((string)($data['name'] ?? ucwords(str_replace(['-', '_'], ' ', $folder)))),
            'description' => trim((string)($data['description'] ?? '')),
            'version' => trim((string)($data['version'] ?? '1.0.0')),
            'author' => trim((string)($data['author'] ?? '')),
            'website' => trim((string)($data['website'] ?? '')),
            'default_position' => trim((string)($data['default_position'] ?? 'left')),
            'default_panel_name' => trim((string)($data['default_panel_name'] ?? (($data['name'] ?? $folder) . ' Panel'))),
            'admin' => !empty($data['admin']),
            'bootstrap' => !empty($data['bootstrap']),
            'panel' => !empty($data['panel']),
            'schema' => !empty($data['schema']),
            'upgrade' => !empty($data['upgrade']),
            'dependencies' => is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [],
            'permissions' => is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            'admin_menu' => is_array($data['admin_menu'] ?? null) ? $data['admin_menu'] : [],
            'min_core_version' => trim((string)($data['min_core_version'] ?? '1.0.0')),
            'module_class' => $moduleClass,
            'hooks' => is_array($data['hooks'] ?? null) ? $data['hooks'] : [],
            'provides' => is_array($data['provides'] ?? null) ? $data['provides'] : [],
            'settings_page' => trim((string)($data['settings_page'] ?? '')),
            'sdk' => [
                'enabled' => $moduleClass !== '' || !empty($data['sdk']['enabled']),
                'module_class' => $moduleClass,
            ],
        ];
    }
}
