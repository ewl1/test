<?php

namespace App\MiniCMS\Infusions;

interface ModulePresentationContract
{
    /**
     * Grazina modulio korteles ir detalaus rodinio pateikimo metaduomenis.
     *
     * Rekomenduojamas formatas:
     * - `card.badges`: papildomi badge modulio kortelei
     * - `card.meta`: trumpi meta laukeliai kortelei
     * - `card.summary`: trumpos santraukos eilutes kortelei
     * - `detail.sections`: detalaus rodinio sekcijos
     *
     * Rezervuoti core badge raktai:
     * - `sdk`
     * - `legacy`
     * - `has_migrations`
     * - `upgrade_available`
     * - `missing_manifest`
     *
     * @return array<string, mixed>
     */
    public function presentationMetadata(): array;
}
