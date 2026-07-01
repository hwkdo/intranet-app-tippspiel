<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsImagePortInterface;
use Illuminate\Support\Facades\Log;

class UnsupportedNewsImagePort implements TippspielAiNewsImagePortInterface
{
    public function generateTitleImage(string $prompt, array $referenceImageUrls): ?string
    {
        Log::warning('Tippspiel: KI-Titelbilder werden für den gewählten Provider nicht unterstützt.');

        return null;
    }
}
