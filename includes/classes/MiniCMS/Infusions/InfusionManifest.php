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

    private static function normalizeStringList($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $normalized[] = $item;
            }
        }

        return array_values(array_unique($normalized));
    }

    private static function normalizeLocalePrefix(string $folder, $value): string
    {
        $prefix = trim((string)$value);
        if ($prefix === '') {
            $prefix = trim($folder) . '.manifest';
        }

        return trim($prefix, ". \t\n\r\0\x0B");
    }

    private static function localeSegment($value): string
    {
        $segment = trim((string)$value);
        if ($segment === '') {
            return '';
        }

        $segment = strtolower($segment);
        $segment = preg_replace('/[^a-z0-9]+/i', '_', $segment) ?? '';
        return trim($segment, '_');
    }

    private static function localizeString($key, string $fallback): string
    {
        $fallback = trim($fallback);
        $key = trim((string)$key);
        if ($key === '') {
            return $fallback;
        }

        if (function_exists('\\__')) {
            return trim((string) \__($key, [], $fallback));
        }

        return $fallback;
    }

    private static function localizeField(array $data, string $valueField, string $keyField, ?string $conventionKey, string $fallback): string
    {
        $fallbackValue = trim((string)($data[$valueField] ?? $fallback));
        $localeKey = trim((string)($data[$keyField] ?? ($conventionKey ?? '')));
        return self::localizeString($localeKey, $fallbackValue);
    }

    private static function localizeStringListWithFallbacks(array $defaults, array $keys): array
    {
        $defaults = self::normalizeStringList($defaults);
        $keys = self::normalizeStringList($keys);

        if (!$keys) {
            return $defaults;
        }

        $localized = [];
        $max = max(count($defaults), count($keys));
        for ($index = 0; $index < $max; $index++) {
            $fallback = $defaults[$index] ?? '';
            $key = $keys[$index] ?? '';
            if ($key !== '') {
                $localized[] = self::localizeString($key, $fallback);
                continue;
            }

            if ($fallback !== '') {
                $localized[] = $fallback;
            }
        }

        return self::normalizeStringList($localized);
    }

    private static function normalizeModuleReferenceList($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $folder = trim((string)($item['folder'] ?? ''));
                $version = trim((string)($item['version'] ?? ''));
                if ($folder !== '') {
                    $normalized[] = [
                        'folder' => $folder,
                        'version' => $version,
                    ];
                }
                continue;
            }

            $folder = trim((string)$item);
            if ($folder !== '') {
                $normalized[] = [
                    'folder' => $folder,
                    'version' => '',
                ];
            }
        }

        return $normalized;
    }

    private static function normalizeProvides($provides): array
    {
        if (!is_array($provides)) {
            return [];
        }

        $normalized = [];
        foreach ($provides as $key => $value) {
            if (is_int($key)) {
                $value = trim((string)$value);
                if ($value !== '') {
                    $normalized[] = $value;
                }
                continue;
            }

            $bucket = [];
            if (is_array($value)) {
                $bucket = self::normalizeStringList($value);
            } else {
                $single = trim((string)$value);
                if ($single !== '') {
                    $bucket[] = $single;
                }
            }

            $normalized[(string)$key] = $bucket;
        }

        return $normalized;
    }

    private static function normalizePermissions($permissions, string $localePrefix): array
    {
        if (!is_array($permissions)) {
            return [];
        }

        $normalized = [];
        foreach ($permissions as $index => $permission) {
            if (!is_array($permission)) {
                continue;
            }

            $slug = trim((string)($permission['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $segment = self::localeSegment($slug !== '' ? $slug : $index);
            $baseKey = $localePrefix !== '' && $segment !== '' ? $localePrefix . '.permissions.' . $segment : null;
            $nameKey = trim((string)($permission['name_key'] ?? ($baseKey ? $baseKey . '.name' : '')));
            $descriptionKey = trim((string)($permission['description_key'] ?? ($baseKey ? $baseKey . '.description' : '')));

            $normalized[] = array_replace($permission, [
                'slug' => $slug,
                'name_key' => $nameKey,
                'description_key' => $descriptionKey,
                'name' => self::localizeString($nameKey, trim((string)($permission['name'] ?? $slug))),
                'description' => self::localizeString($descriptionKey, trim((string)($permission['description'] ?? ''))),
            ]);
        }

        return $normalized;
    }

    private static function normalizeAdminMenu($items, string $localePrefix): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $slug = trim((string)($item['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $segment = self::localeSegment($slug !== '' ? $slug : $index);
            $baseKey = $localePrefix !== '' && $segment !== '' ? $localePrefix . '.admin_menu.' . $segment : null;
            $titleKey = trim((string)($item['title_key'] ?? ($baseKey ? $baseKey . '.title' : '')));

            $normalized[] = array_replace($item, [
                'slug' => $slug,
                'title_key' => $titleKey,
                'title' => self::localizeString($titleKey, trim((string)($item['title'] ?? $slug))),
                'permission' => trim((string)($item['permission'] ?? '')),
                'sort_order' => (int)($item['sort_order'] ?? 0),
            ]);
        }

        return $normalized;
    }

    private static function normalizeChangelog($changelog, string $localePrefix): array
    {
        if (!is_array($changelog)) {
            return [];
        }

        $normalized = [];
        foreach ($changelog as $index => $entry) {
            if (is_string($entry)) {
                $entry = trim($entry);
                if ($entry !== '') {
                    $normalized[] = [
                        'version' => '',
                        'title' => '',
                        'date' => '',
                        'notes' => [$entry],
                        'title_key' => '',
                        'notes_keys' => [],
                    ];
                }
                continue;
            }

            if (!is_array($entry)) {
                continue;
            }

            $version = trim((string)($entry['version'] ?? ''));
            $segment = self::localeSegment($version !== '' ? $version : ($index + 1));
            $baseKey = $localePrefix !== '' && $segment !== '' ? $localePrefix . '.changelog.' . $segment : null;
            $titleKey = trim((string)($entry['title_key'] ?? ($baseKey ? $baseKey . '.title' : '')));
            $date = trim((string)($entry['date'] ?? ''));
            $noteDefaults = self::normalizeStringList((array)($entry['notes'] ?? []));
            $noteKeys = self::normalizeStringList($entry['notes_keys'] ?? []);
            if (!$noteKeys && $baseKey && $noteDefaults) {
                foreach (array_keys($noteDefaults) as $noteIndex) {
                    $noteKeys[] = $baseKey . '.notes.' . ($noteIndex + 1);
                }
            }

            $title = self::localizeString($titleKey, trim((string)($entry['title'] ?? '')));
            $notes = self::localizeStringListWithFallbacks($noteDefaults, $noteKeys);

            if ($version === '' && $title === '' && !$notes) {
                continue;
            }

            $normalized[] = [
                'version' => $version,
                'title' => $title,
                'date' => $date,
                'notes' => $notes,
                'title_key' => $titleKey,
                'notes_keys' => $noteKeys,
            ];
        }

        return $normalized;
    }

    private static function normalizeLocalizedNotes($defaults, $keys, string $localePrefix, string $group): array
    {
        $defaultItems = self::normalizeStringList((array)$defaults);
        $keyItems = self::normalizeStringList($keys ?? []);

        if (!$keyItems && $localePrefix !== '' && $defaultItems) {
            foreach (array_keys($defaultItems) as $index) {
                $keyItems[] = $localePrefix . '.' . $group . '.' . ($index + 1);
            }
        }

        return self::localizeStringListWithFallbacks($defaultItems, $keyItems);
    }

    private static function normalize(string $folder, array $data): array
    {
        $moduleClass = trim((string)($data['module_class'] ?? ($data['sdk']['module_class'] ?? '')));
        $localePrefix = self::normalizeLocalePrefix($folder, $data['locale_prefix'] ?? '');
        $defaultName = ucwords(str_replace(['-', '_'], ' ', $folder));
        $defaultDescription = '';
        $defaultPanelName = trim((string)($data['name'] ?? $defaultName)) . ' Panel';

        return [
            'folder' => $folder,
            'locale_prefix' => $localePrefix,
            'name_key' => trim((string)($data['name_key'] ?? ($localePrefix !== '' ? $localePrefix . '.name' : ''))),
            'description_key' => trim((string)($data['description_key'] ?? ($localePrefix !== '' ? $localePrefix . '.description' : ''))),
            'default_panel_name_key' => trim((string)($data['default_panel_name_key'] ?? ($localePrefix !== '' ? $localePrefix . '.default_panel_name' : ''))),
            'name' => self::localizeField($data, 'name', 'name_key', $localePrefix !== '' ? $localePrefix . '.name' : null, $defaultName),
            'description' => self::localizeField($data, 'description', 'description_key', $localePrefix !== '' ? $localePrefix . '.description' : null, $defaultDescription),
            'version' => trim((string)($data['version'] ?? '1.0.0')),
            'author' => trim((string)($data['author'] ?? '')),
            'website' => trim((string)($data['website'] ?? '')),
            'default_position' => trim((string)($data['default_position'] ?? 'left')),
            'default_panel_name' => self::localizeField($data, 'default_panel_name', 'default_panel_name_key', $localePrefix !== '' ? $localePrefix . '.default_panel_name' : null, $defaultPanelName),
            'admin' => !empty($data['admin']),
            'bootstrap' => !empty($data['bootstrap']),
            'panel' => !empty($data['panel']),
            'schema' => !empty($data['schema']),
            'upgrade' => !empty($data['upgrade']),
            'dependencies' => self::normalizeModuleReferenceList($data['dependencies'] ?? []),
            'conflicts' => self::normalizeModuleReferenceList($data['conflicts'] ?? []),
            'permissions' => self::normalizePermissions($data['permissions'] ?? [], $localePrefix),
            'admin_menu' => self::normalizeAdminMenu($data['admin_menu'] ?? [], $localePrefix),
            'min_core_version' => trim((string)($data['min_core_version'] ?? '1.0.0')),
            'min_php_version' => trim((string)($data['min_php_version'] ?? '8.0.0')),
            'required_extensions' => self::normalizeStringList($data['required_extensions'] ?? []),
            'module_class' => $moduleClass,
            'hooks' => is_array($data['hooks'] ?? null) ? $data['hooks'] : [],
            'provides' => self::normalizeProvides($data['provides'] ?? []),
            'changelog' => self::normalizeChangelog($data['changelog'] ?? [], $localePrefix),
            'upgrade_notes_keys' => self::normalizeStringList($data['upgrade_notes_keys'] ?? []),
            'rollback_notes_keys' => self::normalizeStringList($data['rollback_notes_keys'] ?? []),
            'upgrade_notes' => self::normalizeLocalizedNotes($data['upgrade_notes'] ?? [], $data['upgrade_notes_keys'] ?? [], $localePrefix, 'upgrade_notes'),
            'rollback_notes' => self::normalizeLocalizedNotes($data['rollback_notes'] ?? [], $data['rollback_notes_keys'] ?? [], $localePrefix, 'rollback_notes'),
            'settings_page' => trim((string)($data['settings_page'] ?? '')),
            'sdk' => [
                'enabled' => $moduleClass !== '' || !empty($data['sdk']['enabled']),
                'module_class' => $moduleClass,
            ],
        ];
    }
}
