<?php

namespace App\MiniCMS\Infusions;

interface ModuleDiagnosticsContract
{
    /**
     * Grazina modulio health check sarasa.
     *
     * Rekomenduojamas formatas:
     * - `key`: check identifikatorius
     * - `label`: matomas pavadinimas
     * - `status`: `ok`, `warning`, `error`, `info`
     * - `message`: trumpa santrauka
     *
     * @return array<int, array<string, mixed>>
     */
    public function diagnosticsHealthChecks(): array;

    /**
     * Grazina trukstamus modulio failus.
     *
     * Rekomenduojamas formatas:
     * - `path`: failo kelias
     * - `required`: ar failas butinas
     * - `message`: papildoma pastaba
     *
     * @return array<int, array<string, mixed>>
     */
    public function diagnosticsMissingFiles(): array;

    /**
     * Grazina trukstamas DB lenteles.
     *
     * Rekomenduojamas formatas:
     * - `table`: lenteles pavadinimas
     * - `required`: ar lentele butina
     * - `message`: papildoma pastaba
     *
     * @return array<int, array<string, mixed>>
     */
    public function diagnosticsMissingTables(): array;

    /**
     * Grazina konfiguracijos busenas.
     *
     * Rekomenduojamas formatas:
     * - `key`: konfiguracijos raktas
     * - `label`: matomas pavadinimas
     * - `status`: `ok`, `warning`, `error`, `info`
     * - `value`: dabartine reiksme
     * - `expected`: tiketine reiksme
     * - `message`: papildoma santrauka
     *
     * @return array<int, array<string, mixed>>
     */
    public function diagnosticsConfigurationStates(): array;
}
