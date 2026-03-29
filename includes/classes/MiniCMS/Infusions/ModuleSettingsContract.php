<?php

namespace App\MiniCMS\Infusions;

interface ModuleSettingsContract
{
    /**
     * Grazina modulio nustatymu sekcijas.
     *
     * Rekomenduojamas formatas:
     * - `key`: sekcijos identifikatorius
     * - `title`: pavadinimas
     * - `description`: trumpas paaiskinimas
     * - `icon`: pasirenkama ikona
     *
     * @return array<int, array<string, mixed>>
     */
    public function settingsSections(): array;

    /**
     * Grazina nustatymu formos schema.
     *
     * Rekomenduojamas lauko formatas:
     * - `key`: nustatymo raktas
     * - `type`: lauko tipas (`text`, `textarea`, `select`, `checkbox`, ...)
     * - `label`: matomas pavadinimas
     * - `section`: sekcijos raktas
     * - `default`: numatyta reiksme
     * - `options`: pasirinkimai, jei jie reikalingi
     *
     * Leidziama naudoti ir grupes su `fields`, jei schema reikia skaidyti smulkiau.
     *
     * @return array<int, array<string, mixed>>
     */
    public function settingsFormSchema(): array;

    /**
     * Grazina validavimo taisykles pagal nustatymo rakta.
     *
     * Rekomenduojamas formatas:
     * - `required`
     * - `type`
     * - `min`, `max`
     * - `choices`
     * - `pattern`
     * - `sanitize`
     *
     * @return array<string, array<string, mixed>>
     */
    public function settingsValidationRules(): array;
}
