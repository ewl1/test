<?php

namespace App\MiniCMS\Infusions;

use RuntimeException;

final class InfusionSdk
{
    private static ?HookRegistry $hooks = null;

    public static function manifest(string $folder, ?array $data = null): InfusionManifest
    {
        return $data !== null
            ? InfusionManifest::fromArray($folder, $data)
            : InfusionManifest::fromFile($folder);
    }

    public static function context(string $folder, int $infusionId = 0, ?array $manifest = null): InfusionContext
    {
        return new InfusionContext($folder, $infusionId, $manifest ?? []);
    }

    public static function hooks(): HookRegistry
    {
        if (self::$hooks === null) {
            self::$hooks = new HookRegistry();
        }

        return self::$hooks;
    }

    public static function module(string $folder, int $infusionId = 0, ?array $manifestData = null): ?InfusionModuleInterface
    {
        $manifest = self::manifest($folder, $manifestData);
        $explicitClass = $manifest->moduleClass();
        $moduleClass = $explicitClass ?: $manifest->defaultModuleClass();

        if (!class_exists($moduleClass)) {
            if ($explicitClass !== null) {
                throw new RuntimeException('Manifest nurodyta modulio klase nerasta: ' . $moduleClass);
            }

            return null;
        }

        $module = new $moduleClass(self::context($folder, $infusionId, $manifest->toArray()));
        if (!$module instanceof InfusionModuleInterface) {
            throw new RuntimeException('Infusion klase turi igyvendinti InfusionModuleInterface: ' . $moduleClass);
        }

        return $module;
    }
}
