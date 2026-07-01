<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Contracts;

interface TippspielAiNewsImagePortInterface
{
    /**
     * Generiert ein Titelbild und gibt den Pfad zu einer temporären Bilddatei zurück.
     *
     * @param  list<string>  $referenceImageUrls  URLs zu Team-Wappen als Referenz
     */
    public function generateTitleImage(string $prompt, array $referenceImageUrls): ?string;
}
