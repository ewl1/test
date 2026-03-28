<?php

namespace App\MiniCMS\Infusions;

interface InfusionModuleInterface
{
    public function boot(): void;

    public function install(): void;

    public function upgrade(string $installedVersion, string $targetVersion): void;

    public function uninstall(): void;

    public function renderAdmin(): string;

    public function renderPanel(array $panelData = []): string;

    public function registerHooks(HookRegistry $hooks): void;
}
