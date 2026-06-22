<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Contracts;

use Hwkdo\IntranetAppTippspiel\Models\Season;

interface TippspielAiNewsPortInterface
{
    /**
     * Generiert einen News-Artikel für den abgeschlossenen Spieltag.
     *
     * @param  array<int, array{home: string, away: string, homeScore: int, awayScore: int, topTipper: string|null, points: int}>  $matchResults
     */
    public function generateMatchdayNews(Season $season, int $matchday, array $matchResults): string;
}
