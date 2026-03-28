<?php

namespace App\MiniCMS\Infusions;

abstract class AbstractInfusionModule implements InfusionModuleInterface
{
    protected InfusionContext $context;

    public function __construct(InfusionContext $context)
    {
        $this->context = $context;
    }

    public function boot(): void
    {
    }

    public function install(): void
    {
        if ($this->context->has('schema.php')) {
            $INFUSION = [
                'id' => $this->context->id(),
                'folder' => $this->context->folder(),
                'manifest' => $this->context->manifest(),
            ];
            include $this->context->path('schema.php');
        }
    }

    public function upgrade(string $installedVersion, string $targetVersion): void
    {
        if ($this->context->has('upgrade.php')) {
            $INFUSION = [
                'id' => $this->context->id(),
                'folder' => $this->context->folder(),
                'manifest' => $this->context->manifest(),
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
            ];
            include $this->context->path('upgrade.php');
        }
    }

    public function uninstall(): void
    {
        if ($this->context->has('uninstall.php')) {
            $INFUSION = [
                'id' => $this->context->id(),
                'folder' => $this->context->folder(),
                'manifest' => $this->context->manifest(),
            ];
            include $this->context->path('uninstall.php');
        }
    }

    public function renderAdmin(): string
    {
        return $this->context->has('admin.php')
            ? $this->context->includeFile('admin.php')
            : '';
    }

    public function renderPanel(array $panelData = []): string
    {
        return $this->context->has('panel.php')
            ? $this->context->includeFile('panel.php', ['panelData' => $panelData])
            : '';
    }

    public function registerHooks(HookRegistry $hooks): void
    {
    }

    protected function registerStyle(string $relativePath): void
    {
        if (function_exists('register_page_style')) {
            register_page_style($this->context->publicPath($relativePath));
        }
    }

    protected function registerScript(string $relativePath): void
    {
        if (function_exists('register_page_script')) {
            register_page_script($this->context->publicPath($relativePath));
        }
    }

    protected function migrationsPath(string $relative = ''): string
    {
        return $this->context->migrationsPath($relative);
    }

    protected function hasMigrations(): bool
    {
        return $this->context->hasMigrations();
    }
}
