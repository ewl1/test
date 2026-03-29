<?php

namespace App\MiniCMS\Infusions;

interface ModuleEventContract
{
    /**
     * Grazina modulio publikuojamu ivykiu deklaracijas.
     *
     * Rekomenduojamas formatas:
     * - `key`: ivykio identifikatorius (`forum.topic.created`)
     * - `type`: ivykio tipas (`created`, `updated`, `deleted`, `reaction`, ...)
     * - `title`: matomas pavadinimas
     * - `summary`: trumpa santrauka
     * - `actor`: actor modelio schema
     * - `target`: target modelio schema
     * - `visibility`: matomumo taisykles
     * - `channels`: `notifications`, `activity_feed` arba abu
     *
     * @return array<int, array<string, mixed>>
     */
    public function publishedEvents(): array;
}
