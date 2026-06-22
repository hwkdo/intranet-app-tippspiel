<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Contracts;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;

interface FootballDataProviderInterface
{
    /**
     * Gibt verfügbare Saisons für einen Wettbewerb zurück.
     *
     * @return array<int, array{id: int, startDate: string, endDate: string, currentMatchday: int|null}>
     */
    public function fetchCompetitionSeasons(string $competitionCode): array;

    /**
     * Gibt alle Spiele einer Saison zurück (ein einziger API-Call).
     *
     * @return array<int, array{
     *     id: int,
     *     matchday: int|null,
     *     stage: string,
     *     group: string|null,
     *     utcDate: string,
     *     status: string,
     *     homeTeam: array{name: string, crest: string|null},
     *     awayTeam: array{name: string, crest: string|null},
     *     score: array{fullTime: array{home: int|null, away: int|null}}
     * }>
     */
    public function fetchMatches(Season $season): array;

    /**
     * Gibt die aktuellen Ergebnisse für die Spiele eines bestimmten Spieltages zurück.
     *
     * @return array<int, array{
     *     id: int,
     *     status: string,
     *     score: array{fullTime: array{home: int|null, away: int|null}}
     * }>
     */
    public function fetchMatchdayResults(Season $season, int $matchday): array;
}
