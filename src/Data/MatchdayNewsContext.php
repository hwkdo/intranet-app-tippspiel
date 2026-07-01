<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Data;

/**
 * Aggregierte Fakten für die KI-Spieltagsberichterstattung.
 */
readonly class MatchdayNewsContext
{
    /**
     * @param  array<int, array{
     *     home: string,
     *     away: string,
     *     homeScore: int,
     *     awayScore: int,
     *     tipCount: int,
     *     averagePoints: float,
     *     exactTips: int,
     *     zeroTips: int,
     *     topTipper: string|null,
     *     topPoints: int,
     * }>  $matches
     * @param  array{
     *     topScorers: list<array{user_name: string, round_points: int}>,
     *     lowScorers: list<array{user_name: string, round_points: int}>,
     *     zeroScorers: list<string>,
     *     averageRoundPoints: float,
     *     participantCount: int,
     * }  $roundHighlights
     * @param  array{
     *     easiestMatch: array{label: string, averagePoints: float, exactTips: int, tipCount: int}|null,
     *     hardestMatch: array{label: string, averagePoints: float, zeroTips: int, tipCount: int}|null,
     *     matches: list<array{label: string, averagePoints: float, exactTips: int, zeroTips: int, tipCount: int}>,
     * }  $tipAnalysis
     * @param  array{
     *     hasComparison: bool,
     *     newLeader: array{user_name: string, previous_rank: int|null}|null,
     *     previousLeader: array{user_name: string, current_rank: int}|null,
     *     unchangedLeader: string|null,
     *     biggestClimber: array{user_name: string, rank_change: int, current_rank: int, previous_rank: int}|null,
     *     biggestFaller: array{user_name: string, rank_change: int, current_rank: int, previous_rank: int}|null,
     *     changes: list<array{user_name: string, current_rank: int, previous_rank: int|null, rank_change: int|null, total_points: int}>,
     * }  $rankChanges
     * @param  array<int, array{rank: int, user_name: string, total_points: int}>  $currentLeaderboard
     * @param  list<string>  $storylines
     */
    public function __construct(
        public string $seasonName,
        public int $matchday,
        public bool $isFirstMatchday,
        public array $matches,
        public array $roundHighlights,
        public array $tipAnalysis,
        public array $rankChanges,
        public array $currentLeaderboard,
        public array $storylines,
    ) {}
}
