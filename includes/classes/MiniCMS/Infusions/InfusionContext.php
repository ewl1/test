<?php

namespace App\MiniCMS\Infusions;

use PDO;
use RuntimeException;

final class InfusionContext
{
    private string $folder;
    private int $infusionId;
    private array $manifest;

    public function __construct(string $folder, int $infusionId = 0, array $manifest = [])
    {
        $this->folder = trim($folder);
        $this->infusionId = $infusionId;
        $this->manifest = $manifest ?: InfusionManifest::fromFile($this->folder)->toArray();
    }

    public function folder(): string
    {
        return $this->folder;
    }

    public function id(): int
    {
        return $this->infusionId;
    }

    public function manifest(): array
    {
        return $this->manifest;
    }

    public function moduleClass(): string
    {
        $manifest = InfusionManifest::fromArray($this->folder, $this->manifest);
        return $manifest->moduleClass() ?: $manifest->defaultModuleClass();
    }

    public function moduleNamespace(): string
    {
        $class = $this->moduleClass();
        $parts = explode('\\', $class);
        array_pop($parts);
        return implode('\\', $parts) . '\\';
    }

    public function path(string $relative = ''): string
    {
        $base = INFUSIONS . $this->folder . '/';
        return $relative === '' ? $base : $base . ltrim($relative, '/\\');
    }

    public function publicPath(string $relative = ''): string
    {
        $base = 'infusions/' . $this->folder . '/';
        return $relative === '' ? $base : $base . ltrim($relative, '/\\');
    }

    public function has(string $relative): bool
    {
        return is_file($this->path($relative));
    }

    public function pdo(): PDO
    {
        return $GLOBALS['pdo'];
    }

    public function setting(string $key, $default = null)
    {
        if (function_exists('setting')) {
            return setting($key, $default);
        }

        return $default;
    }

    public function includeFile(string $relative, array $vars = []): string
    {
        $file = $this->path($relative);
        if (!is_file($file)) {
            throw new RuntimeException('Infusion failas nerastas: ' . $this->folder . '/' . ltrim($relative, '/\\'));
        }

        ob_start();
        extract($vars, EXTR_SKIP);
        include $file;
        return (string)ob_get_clean();
    }
}
