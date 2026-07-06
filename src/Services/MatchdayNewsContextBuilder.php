<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use Hwkdo\IntranetAppTippspiel\Data\MatchdayNewsContext;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Illuminate\Support\Collection;

class MatchdayNewsContextBuilder
{
    public function __construct(
        private readonly TipEvaluationService $evaluationService,
    ) {}

    public function build(Season $season, string $roundKey): ?MatchdayNewsContext
    {
        $round = $season->availableRounds(tippableOnly: false)
            ->firstWhere('key', $roundKey);

        if ($round === null) {
            return null;
        }

        $matches = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->forRoundKey($roundKey)
            ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->with(['tips' => fn ($q) => $q->whereNotNull('points_earned')->with('participant.user')])
            ->orderBy('kickoff_at')
            ->get();

        if ($matches->isEmpty()) {
            return null;
        }

        $previousRoundKey = $this->evaluationService->previousRoundKey($season, $roundKey);
        $isFirstRound = $previousRoundKey === null;
        $matchStats = $this->buildMatchStats($matches, $season);
        $roundLeaderboard = $this->evaluationService->getRoundLeaderboard($season, $roundKey);
        $roundHighlights = $this->buildRoundHighlights($roundLeaderboard);
        $tipAnalysis = $this->buildTipAnalysis($matchStats);
        $currentLeaderboard = $this->evaluationService->leaderboardUpToRound($season, $roundKey);
        $previousLeaderboard = $previousRoundKey !== null
            ? $this->evaluationService->leaderboardUpToRound($season, $previousRoundKey)
            : [];
        $rankChanges = $this->buildRankChanges($previousLeaderboard, $currentLeaderboard, $isFirstRound);

        $storylines = $this->buildStorylines(
            $round->label,
            $roundHighlights,
            $tipAnalysis,
            $rankChanges,
            $isFirstRound,
        );

        return new MatchdayNewsContext(
            seasonName: $season->name,
            roundKey: $roundKey,
            roundLabel: $round->label,
            isFirstRound: $isFirstRound,
            matches: $matchStats,
            roundHighlights: $roundHighlights,
            tipAnalysis: $tipAnalysis,
            rankChanges: $rankChanges,
            currentLeaderboard: $currentLeaderboard,
            storylines: $storylines,
        );
    }

    /**
     * @param  Collection<int, TippspielMatch>  $matches
     * @return array<int, array{
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
     * }>
     */
    private function buildMatchStats(Collection $matches, Season $season): array
    {
        return $matches->map(function (TippspielMatch $match) use ($season) {
            $evaluatedTips = $match->tips;
            $tipCount = $evaluatedTips->count();
            $averagePoints = $tipCount > 0
                ? round((float) $evaluatedTips->avg('points_earned'), 2)
                : 0.0;
            $topTip = $evaluatedTips->sortByDesc('points_earned')->first();

            return [
                'home' => $match->home_team_name,
                'away' => $match->away_team_name,
                'homeScore' => (int) $match->home_score,
                'awayScore' => (int) $match->away_score,
                'tipCount' => $tipCount,
                'averagePoints' => $averagePoints,
                'exactTips' => $evaluatedTips->where('points_earned', $season->points_exact_result)->count(),
                'zeroTips' => $evaluatedTips->where('points_earned', 0)->count(),
                'topTipper' => $topTip?->participant?->user?->name,
                'topPoints' => (int) ($topTip?->points_earned ?? 0),
            ];
        })->values()->toArray();
    }

    /**
     * @param  array<int, array{rank: int, participant_id: int, user_id: int, user_name: string, round_points: int, tips_count: int, evaluated_count: int}>  $roundLeaderboard
     * @return array{
     *     topScorers: list<array{user_name: string, round_points: int}>,
     *     lowScorers: list<array{user_name: string, round_points: int}>,
     *     zeroScorers: list<string>,
     *     averageRoundPoints: float,
     *     participantCount: int,
     * }
     */
    private function buildRoundHighlights(array $roundLeaderboard): array
    {
        if ($roundLeaderboard === []) {
            return [
                'topScorers' => [],
                'lowScorers' => [],
                'zeroScorers' => [],
                'averageRoundPoints' => 0.0,
                'participantCount' => 0,
            ];
        }

        $maxPoints = $roundLeaderboard[0]['round_points'];
        $minPoints = $roundLeaderboard[array_key_last($roundLeaderboard)]['round_points'];

        $topScorers = collect($roundLeaderboard)
            ->filter(fn (array $entry) => $entry['round_points'] === $maxPoints)
            ->map(fn (array $entry) => ['user_name' => $entry['user_name'], 'round_points' => $entry['round_points']])
            ->values()
            ->all();

        $lowScorers = collect($roundLeaderboard)
            ->filter(fn (array $entry) => $entry['round_points'] === $minPoints)
            ->map(fn (array $entry) => ['user_name' => $entry['user_name'], 'round_points' => $entry['round_points']])
            ->values()
            ->all();

        $zeroScorers = collect($roundLeaderboard)
            ->filter(fn (array $entry) => $entry['round_points'] === 0)
            ->pluck('user_name')
            ->all();

        $averageRoundPoints = round(
            collect($roundLeaderboard)->avg('round_points') ?? 0.0,
            2,
        );

        return [
            'topScorers' => $topScorers,
            'lowScorers' => $lowScorers,
            'zeroScorers' => $zeroScorers,
            'averageRoundPoints' => $averageRoundPoints,
            'participantCount' => count($roundLeaderboard),
        ];
    }

    /**
     * @param  array<int, array{home: string, away: string, homeScore: int, awayScore: int, tipCount: int, averagePoints: float, exactTips: int, zeroTips: int, topTipper: string|null, topPoints: int}>  $matchStats
     * @return array{
     *     easiestMatch: array{label: string, averagePoints: float, exactTips: int, tipCount: int}|null,
     *     hardestMatch: array{label: string, averagePoints: float, zeroTips: int, tipCount: int}|null,
     *     matches: list<array{label: string, averagePoints: float, exactTips: int, zeroTips: int, tipCount: int}>,
     * }
     */
    private function buildTipAnalysis(array $matchStats): array
    {
        $matches = collect($matchStats)
            ->filter(fn (array $match) => $match['tipCount'] > 0)
            ->map(fn (array $match) => [
                'label' => "{$match['home']} vs. {$match['away']} ({$match['homeScore']}:{$match['awayScore']})",
                'averagePoints' => $match['averagePoints'],
                'exactTips' => $match['exactTips'],
                'zeroTips' => $match['zeroTips'],
                'tipCount' => $match['tipCount'],
            ])
            ->values();

        $easiest = $matches->sortByDesc('averagePoints')->first();
        $hardest = $matches->sortBy('averagePoints')->first();

        return [
            'easiestMatch' => $easiest !== null ? [
                'label' => $easiest['label'],
                'averagePoints' => $easiest['averagePoints'],
                'exactTips' => $easiest['exactTips'],
                'tipCount' => $easiest['tipCount'],
            ] : null,
            'hardestMatch' => $hardest !== null ? [
                'label' => $hardest['label'],
                'averagePoints' => $hardest['averagePoints'],
                'zeroTips' => $hardest['zeroTips'],
                'tipCount' => $hardest['tipCount'],
            ] : null,
            'matches' => $matches->all(),
        ];
    }

    /**
     * @param  array<int, array{rank: int, participant_id: int, user_name: string, total_points: int}>  $previousLeaderboard
     * @param  array<int, array{rank: int, participant_id: int, user_name: string, total_points: int}>  $currentLeaderboard
     * @return array{
     *     hasComparison: bool,
     *     newLeader: array{user_name: string, previous_rank: int|null}|null,
     *     previousLeader: array{user_name: string, current_rank: int}|null,
     *     unchangedLeader: string|null,
     *     biggestClimber: array{user_name: string, rank_change: int, current_rank: int, previous_rank: int}|null,
     *     biggestFaller: array{user_name: string, rank_change: int, current_rank: int, previous_rank: int}|null,
     *     changes: list<array{user_name: string, current_rank: int, previous_rank: int|null, rank_change: int|null, total_points: int}>,
     * }
     */
    private function buildRankChanges(array $previousLeaderboard, array $currentLeaderboard, bool $isFirstRound): array
    {
        if ($isFirstRound || $currentLeaderboard === []) {
            return [
                'hasComparison' => false,
                'newLeader' => null,
                'previousLeader' => null,
                'unchangedLeader' => null,
                'biggestClimber' => null,
                'biggestFaller' => null,
                'changes' => [],
            ];
        }

        $previousByParticipant = collect($previousLeaderboard)->keyBy('participant_id');
        $changes = collect($currentLeaderboard)->map(function (array $entry) use ($previousByParticipant) {
            $previous = $previousByParticipant->get($entry['participant_id']);
            $previousRank = $previous['rank'] ?? null;
            $rankChange = $previousRank !== null ? $previousRank - $entry['rank'] : null;

            return [
                'user_name' => $entry['user_name'],
                'current_rank' => $entry['rank'],
                'previous_rank' => $previousRank,
                'rank_change' => $rankChange,
                'total_points' => $entry['total_points'],
            ];
        });

        $currentLeader = $currentLeaderboard[0] ?? null;
        $previousLeader = $previousLeaderboard[0] ?? null;

        $newLeader = null;
        $previousLeaderInfo = null;
        $unchangedLeader = null;

        if ($currentLeader !== null && $previousLeader !== null) {
            if ($currentLeader['participant_id'] !== $previousLeader['participant_id']) {
                $newLeaderEntry = $changes->firstWhere('user_name', $currentLeader['user_name']);
                $newLeader = [
                    'user_name' => $currentLeader['user_name'],
                    'previous_rank' => $newLeaderEntry['previous_rank'] ?? null,
                ];
                $previousLeaderEntry = $changes->firstWhere('user_name', $previousLeader['user_name']);
                if ($previousLeaderEntry !== null) {
                    $previousLeaderInfo = [
                        'user_name' => $previousLeader['user_name'],
                        'current_rank' => $previousLeaderEntry['current_rank'],
                    ];
                }
            } else {
                $unchangedLeader = $currentLeader['user_name'];
            }
        }

        $rankedChanges = $changes->filter(fn (array $c) => $c['rank_change'] !== null);

        $biggestClimber = $rankedChanges
            ->sortByDesc('rank_change')
            ->filter(fn (array $c) => $c['rank_change'] > 0)
            ->first();

        $biggestFaller = $rankedChanges
            ->sortBy('rank_change')
            ->filter(fn (array $c) => $c['rank_change'] < 0)
            ->first();

        return [
            'hasComparison' => true,
            'newLeader' => $newLeader,
            'previousLeader' => $previousLeaderInfo,
            'unchangedLeader' => $unchangedLeader,
            'biggestClimber' => $biggestClimber !== null ? [
                'user_name' => $biggestClimber['user_name'],
                'rank_change' => $biggestClimber['rank_change'],
                'current_rank' => $biggestClimber['current_rank'],
                'previous_rank' => $biggestClimber['previous_rank'],
            ] : null,
            'biggestFaller' => $biggestFaller !== null ? [
                'user_name' => $biggestFaller['user_name'],
                'rank_change' => $biggestFaller['rank_change'],
                'current_rank' => $biggestFaller['current_rank'],
                'previous_rank' => $biggestFaller['previous_rank'],
            ] : null,
            'changes' => $changes->all(),
        ];
    }

    /**
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
     * @return list<string>
     */
    private function buildStorylines(
        string $roundLabel,
        array $roundHighlights,
        array $tipAnalysis,
        array $rankChanges,
        bool $isFirstRound,
    ): array {
        $lines = [];

        if ($roundHighlights['topScorers'] !== []) {
            $names = collect($roundHighlights['topScorers'])->pluck('user_name')->implode(', ');
            $points = $roundHighlights['topScorers'][0]['round_points'];
            $lines[] = "Runden-Held(en): {$names} mit {$points} Punkten in {$roundLabel}.";
        }

        if ($roundHighlights['lowScorers'] !== [] && $roundHighlights['participantCount'] > 1) {
            $names = collect($roundHighlights['lowScorers'])->pluck('user_name')->implode(', ');
            $points = $roundHighlights['lowScorers'][0]['round_points'];
            $lines[] = "Schwache Runde für: {$names} (nur {$points} Punkte).";
        }

        if ($roundHighlights['zeroScorers'] !== []) {
            $lines[] = 'Ohne Punkte: '.implode(', ', $roundHighlights['zeroScorers']).'.';
        }

        if ($tipAnalysis['easiestMatch'] !== null) {
            $match = $tipAnalysis['easiestMatch'];
            $lines[] = "Leichtestes Spiel zum Tippen: {$match['label']} (Ø {$match['averagePoints']} Punkte, {$match['exactTips']} exakte Tipps).";
        }

        if ($tipAnalysis['hardestMatch'] !== null) {
            $match = $tipAnalysis['hardestMatch'];
            $lines[] = "Schwierigstes Spiel: {$match['label']} (Ø {$match['averagePoints']} Punkte, {$match['zeroTips']} Nullpunkte-Tipps).";
        }

        if ($isFirstRound) {
            $lines[] = 'Erste Wertungsrunde der Saison — keine Vergleichsrangliste zur vorherigen Runde.';
        } elseif ($rankChanges['newLeader'] !== null) {
            $leader = $rankChanges['newLeader'];
            $prev = $leader['previous_rank'] !== null ? " (vorher Platz {$leader['previous_rank']})" : '';
            $lines[] = "Neuer Tabellenführer: {$leader['user_name']}{$prev}.";
            if ($rankChanges['previousLeader'] !== null) {
                $former = $rankChanges['previousLeader'];
                $lines[] = "Abgegebene Führung: {$former['user_name']} rutscht auf Platz {$former['current_rank']}.";
            }
        } elseif ($rankChanges['unchangedLeader'] !== null) {
            $lines[] = "Unveränderte Spitze: {$rankChanges['unchangedLeader']} bleibt Tabellenführer.";
        }

        if ($rankChanges['biggestClimber'] !== null) {
            $climber = $rankChanges['biggestClimber'];
            $lines[] = "Größter Aufsteiger: {$climber['user_name']} (Platz {$climber['previous_rank']} → {$climber['current_rank']}, +{$climber['rank_change']} Plätze).";
        }

        if ($rankChanges['biggestFaller'] !== null) {
            $faller = $rankChanges['biggestFaller'];
            $places = abs($faller['rank_change']);
            $lines[] = "Größter Absteiger: {$faller['user_name']} (Platz {$faller['previous_rank']} → {$faller['current_rank']}, −{$places} Plätze).";
        }

        return $lines;
    }
}
