<?php

namespace App\MiniCMS\Infusions;

interface ModuleSearchContract
{
    /**
     * Grazina modulio paieskos saltiniu deklaracijas.
     *
     * Rekomenduojamas formatas:
     * - `key`: stabilus saltinio identifikatorius (`forum_topics`)
     * - `indexed_fields`: indeksuojamu lauku sarasas (`title`, `body`, ...)
     * - `result_url`: rezultato URL sablonas arba route metaduomenys
     * - `title`: pavadinimo saltinis arba laukelis
     * - `summary`: santraukos saltinis arba laukelis
     * - `category`: kategorija, kurioje rodomas rezultatas
     * - `type`: turinio tipas (`thread`, `reply`, `article`, ...)
     * - `permission_filter`: leidimu filtro taisykle ar permission raktas
     * - `weight`: svoris / relevancijos koeficientas
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchMetadata(): array;
}
