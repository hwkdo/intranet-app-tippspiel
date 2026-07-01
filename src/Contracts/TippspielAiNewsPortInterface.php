<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Contracts;

interface TippspielAiNewsPortInterface
{
    public function generateMatchdayNews(string $prompt): string;
}
