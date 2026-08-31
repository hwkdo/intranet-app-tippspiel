<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Hwkdo\IntranetAppTippspiel\Support\TippspielModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipEvaluationService
{
    /**
     * Berechnet die Punkte für einen einzelnen Tipp.
     *
     * Punkteregeln (eine Stufe, nicht additiv):
     * - Exaktes Ergebnis:    tipHome == realHome && tipAway == realAway
     * - Richtige Differenz:  (tipHome - tipAway) == (realHome - realAway) aber nicht exakt
     * - Richtige Tendenz:    Richtung (Sieg/Unentschieden/Niederlage) stimmt, aber nicht Differenz
     * - Falsch:              0 Punkte
     *
     * Differenz impliziert immer Tendenz. Wenn Differenz-Punkte unter Tendenz-Punkten
     * konfiguriert sind (z. B. Differenz = 0), greift mindestens die Tendenz-Stufe.
     */
    public function calculatePoints(
        int $tipHome,
        int $tipAway,
        int $realHome,
        int $realAway,
        int $pointsExact,
        int $pointsDifference,
        int $pointsTendency,
    ): int {
        if ($tipHome === $realHome && $tipAway === $realAway) {
            return $pointsExact;
        }

        $tipDiff = $tipHome - $tipAway;
        $realDiff = $realHome - $realAway;
        $tipTendency = $this->tendency($tipHome, $tipAway);
        $realTendency = $this->tendency($realHome, $realAway);

        if ($tipDiff === $realDiff) {
            return max($pointsDifference, $pointsTendency);
        }

        if ($tipTendency === $realTendency) {
            return $pointsTendency;
        }

        return 0;
    }

    /**
     * Wertet Tips für abgeschlossene Spiele einer Saison aus.
     * Ohne $reevaluate nur Tipps ohne points_earned; mit $reevaluate alle Tipps neu.
     * Bei $dryRun keine Schreibzugriffe — nur Diff-Report.
     *
     * @return array{
     *     evaluated: int,
     *     changed: int,
     *     unchanged: int,
     *     participant_ids: list<int>,
     *     changes: list<array{
     *         tip_id: int,
     *         participant_id: int,
     *         match_id: int,
     *         match_label: string,
     *         tip_score: string,
     *         result_score: string,
     *         old_points: int|null,
     *         new_points: int
     *     }>
     * }
     */
    public function evaluateSeason(
        Season $season,
        ?int $matchday = null,
        bool $reevaluate = false,
        bool $dryRun = false,
    ): array {
        $query = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
            ->whereNotNull('home_score')
            ->whereNotNull('away_score');

        if (! $reevaluate) {
            $query->whereHas('tips', fn ($q) => $q->whereNull('points_earned'));
        } else {
            $query->whereHas('tips');
        }

        if ($matchday !== null) {
            $query->where('matchday', $matchday);
        }

        $matches = $query->with(['tips'])->get();
        $evaluated = 0;
        $changed = 0;
        $unchanged = 0;
        $participantIds = [];
        $changes = [];

        $persist = function () use (
            $season,
            $matches,
            $reevaluate,
            $dryRun,
            &$evaluated,
            &$changed,
            &$unchanged,
            &$participantIds,
            &$changes,
        ): void {
            foreach ($matches as $match) {
                foreach ($match->tips as $tip) {
                    if (! $reevaluate && $tip->points_earned !== null) {
                        continue;
                    }

                    $newPoints = $this->calculatePoints(
                        tipHome: $tip->home_score_tip,
                        tipAway: $tip->away_score_tip,
                        realHome: (int) $match->home_score,
                        realAway: (int) $match->away_score,
                        pointsExact: $season->points_exact_result,
                        pointsDifference: $season->points_correct_difference,
                        pointsTendency: $season->points_correct_tendency,
                    );

                    $oldPoints = $tip->points_earned;
                    $evaluated++;

                    if ($oldPoints === $newPoints) {
                        $unchanged++;

                        continue;
                    }

                    $changed++;
                    $participantIds[] = $tip->participant_id;
                    $changes[] = [
                        'tip_id' => $tip->id,
                        'participant_id' => $tip->participant_id,
                        'match_id' => $match->id,
                        'match_label' => "{$match->home_team_name} {$match->home_score}:{$match->away_score} {$match->away_team_name}",
                        'tip_score' => "{$tip->home_score_tip}:{$tip->away_score_tip}",
                        'result_score' => "{$match->home_score}:{$match->away_score}",
                        'old_points' => $oldPoints,
                        'new_points' => $newPoints,
                    ];

                    if ($dryRun) {
                        continue;
                    }

                    $tip->points_earned = $newPoints;
                    $tip->save();
                }
            }
        };

        if ($dryRun) {
            $persist();
        } else {
            DB::transaction($persist);

            $uniqueParticipantIds = array_values(array_unique($participantIds));
            foreach ($uniqueParticipantIds as $participantId) {
                $participant = $season->participants()->find($participantId);
                $participant?->recalculateTotalPoints();
            }

            Log::info('Tippspiel: Auswertung abgeschlossen', [
                'season' => $season->name,
                'matchday' => $matchday,
                'reevaluate' => $reevaluate,
                'tips_evaluated' => $evaluated,
                'tips_changed' => $changed,
                'participants_updated' => count($uniqueParticipantIds),
            ]);
        }

        return [
            'evaluated' => $evaluated,
            'changed' => $changed,
            'unchanged' => $unchanged,
            'participant_ids' => array_values(array_unique($participantIds)),
            'changes' => $changes,
        ];
    }

    /**
     * Gibt +1 (Heimsieg), -1 (Auswärtssieg) oder 0 (Unentschieden) zurück.
     */
    private function tendency(int $home, int $away): int
    {
        return $home <=> $away;
    }

    /**
     * Gibt zurück, ob alle Spiele einer Runde abgeschlossen sind.
     */
    public function isRoundComplete(Season $season, string $roundKey): bool
    {
        $matches = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->forRoundKey($roundKey)
            ->get();

        if ($matches->isEmpty()) {
            return false;
        }

        return $matches->every(fn (TippspielMatch $match) => $match->isFinished());
    }

    /**
     * Gibt zurück, ob alle Spiele eines Spieltages abgeschlossen sind.
     */
    public function isMatchdayComplete(Season $season, int $matchday): bool
    {
        return $this->isRoundComplete($season, "md:{$matchday}");
    }

    public function previousRoundKey(Season $season, string $roundKey): ?string
    {
        $rounds = $season->availableRounds(tippableOnly: false);
        $index = $rounds->search(fn ($round) => $round->key === $roundKey);

        if ($index === false || $index === 0) {
            return null;
        }

        return $rounds[$index - 1]->key;
    }

    /**
     * @return array<int, array{rank: int, participant_id: int, user_name: string, total_points: int}>
     */
    public function leaderboardUpToRound(Season $season, string $roundKey): array
    {
        $rounds = $season->availableRounds(tippableOnly: false);
        $currentRound = $rounds->firstWhere('key', $roundKey);

        if ($currentRound === null) {
            return [];
        }

        $includedRoundKeys = $rounds
            ->filter(fn ($round) => $round->sortOrder <= $currentRound->sortOrder)
            ->pluck('key');

        $matchIds = collect();

        foreach ($includedRoundKeys as $includedRoundKey) {
            $matchIds = $matchIds->merge(
                TippspielMatch::query()
                    ->where('season_id', $season->id)
                    ->forRoundKey($includedRoundKey)
                    ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
                    ->pluck('id')
            );
        }

        if ($matchIds->isEmpty()) {
            return [];
        }

        $pointsByParticipant = Tip::query()
            ->selectRaw('participant_id, SUM(points_earned) as total_points')
            ->whereIn('match_id', $matchIds)
            ->whereNotNull('points_earned')
            ->groupBy('participant_id')
            ->pluck('total_points', 'participant_id');

        return $season->participants()
            ->with('user')
            ->get()
            ->map(function ($participant) use ($pointsByParticipant) {
                return [
                    'participant_id' => $participant->id,
                    'user_name' => $participant->user?->name ?? 'Unbekannt',
                    'total_points' => (int) ($pointsByParticipant[$participant->id] ?? 0),
                ];
            })
            ->filter(fn (array $entry) => $entry['total_points'] > 0)
            ->sort(function (array $a, array $b) {
                return $b['total_points'] <=> $a['total_points']
                    ?: strcmp($a['user_name'], $b['user_name']);
            })
            ->values()
            ->map(function (array $entry, int $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->toArray();
    }

    /**
     * Gibt die Rangliste für eine Saison zurück (absteigend nach Punkten).
     *
     * @return array<int, array{rank: int, user_name: string, total_points: int, tips_count: int}>
     */
    public function getLeaderboard(Season $season): array
    {
        return $season->participants()
            ->with('user')
            ->withCount('tips')
            ->orderByDesc('total_points')
            ->get()
            ->map(function ($participant, $index) {
                return [
                    'rank' => $index + 1,
                    'participant_id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'user_name' => $participant->user?->name ?? 'Unbekannt',
                    'total_points' => $participant->total_points,
                    'tips_count' => $participant->tips_count,
                ];
            })
            ->toArray();
    }

    /**
     * @return array<int, array{rank: int, participant_id: int, user_id: int, user_name: string, round_points: int, tips_count: int, evaluated_count: int}>
     */
    public function getRoundLeaderboard(Season $season, string $roundKey): array
    {
        $matchIds = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->forRoundKey($roundKey)
            ->pluck('id');

        if ($matchIds->isEmpty()) {
            return [];
        }

        return $season->participants()
            ->with([
                'user',
                'tips' => fn ($query) => $query->whereIn('match_id', $matchIds),
            ])
            ->get()
            ->map(function ($participant) {
                $roundTips = $participant->tips;
                $roundPoints = (int) $roundTips->sum(fn (Tip $tip) => $tip->points_earned ?? 0);

                return [
                    'participant_id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'user_name' => $participant->user?->name ?? 'Unbekannt',
                    'round_points' => $roundPoints,
                    'tips_count' => $roundTips->count(),
                    'evaluated_count' => $roundTips->whereNotNull('points_earned')->count(),
                ];
            })
            ->filter(fn (array $entry) => $entry['tips_count'] > 0)
            ->sort(function (array $a, array $b) {
                return $b['round_points'] <=> $a['round_points']
                    ?: strcmp($a['user_name'], $b['user_name']);
            })
            ->values()
            ->map(function (array $entry, int $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->toArray();
    }

    /**
     * @return array<int, array{rank: int, gvp_id: int, team_name: string, player_count: int, total_points: int, team_points: float, tips_count: int}>
     */
    public function getTeamLeaderboard(Season $season): array
    {
        $participants = $season->participants()
            ->with('user')
            ->withCount('tips')
            ->get();

        return $this->buildTeamLeaderboard(
            $participants,
            fn (Participant $participant): int => $participant->total_points,
            fn (Participant $participant): int => $participant->tips_count,
        );
    }

    /**
     * Saisonübergreifende Einzelwertung (Summe aller jemals erzielten Punkte).
     *
     * @return array<int, array{rank: int, user_id: int, user_name: string, total_points: int, tips_count: int, seasons_count: int}>
     */
    public function getAllTimeLeaderboard(): array
    {
        $tipsByUser = Tip::query()
            ->join(
                'intranet_app_tippspiel_participants',
                'intranet_app_tippspiel_participants.id',
                '=',
                'intranet_app_tippspiel_tips.participant_id'
            )
            ->selectRaw('intranet_app_tippspiel_participants.user_id, COUNT(*) as tips_count')
            ->groupBy('intranet_app_tippspiel_participants.user_id')
            ->pluck('tips_count', 'user_id');

        return Participant::query()
            ->with('user')
            ->select('user_id')
            ->selectRaw('SUM(total_points) as total_points')
            ->selectRaw('COUNT(*) as seasons_count')
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->orderBy('user_id')
            ->get()
            ->map(function (Participant $row) use ($tipsByUser) {
                return [
                    'user_id' => (int) $row->user_id,
                    'user_name' => $row->user?->name ?? 'Unbekannt',
                    'total_points' => (int) $row->total_points,
                    'tips_count' => (int) ($tipsByUser[$row->user_id] ?? 0),
                    'seasons_count' => (int) $row->seasons_count,
                ];
            })
            ->sort(function (array $a, array $b) {
                return $b['total_points'] <=> $a['total_points']
                    ?: strcmp($a['user_name'], $b['user_name']);
            })
            ->values()
            ->map(function (array $entry, int $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->toArray();
    }

    /**
     * Saisonübergreifende Teamwertung.
     * Team-Punkte = Summe aller Einzelpunkte ÷ Anzahl eindeutiger Spieler je GVP.
     *
     * @return array<int, array{rank: int, gvp_id: int, team_name: string, player_count: int, total_points: int, team_points: float, tips_count: int}>
     */
    public function getAllTimeTeamLeaderboard(): array
    {
        $participants = Participant::query()
            ->with('user')
            ->withCount('tips')
            ->get();

        /** @var Collection<int|string|null, Collection<int, Participant>> $grouped */
        $grouped = $participants->groupBy(fn (Participant $participant) => $participant->user?->gvp_id);

        $gvpIds = $grouped->keys()
            ->filter(fn ($gvpId) => $gvpId !== null && $gvpId !== '')
            ->map(fn ($gvpId) => (int) $gvpId)
            ->values();

        if ($gvpIds->isEmpty()) {
            return [];
        }

        $gvpModel = TippspielModels::gvp();
        /** @var Collection<int, Model> $gvps */
        $gvps = $gvpModel::query()->whereIn('id', $gvpIds)->get()->keyBy('id');

        return $grouped
            ->filter(fn (Collection $group, $gvpId) => $gvpId !== null && $gvpId !== '')
            ->map(function (Collection $group, $gvpId) use ($gvps) {
                $playerCount = $group->pluck('user_id')->unique()->count();

                if ($playerCount === 0) {
                    return null;
                }

                $totalPoints = (int) $group->sum(fn (Participant $participant) => (int) $participant->total_points);
                $gvp = $gvps->get((int) $gvpId);

                return [
                    'gvp_id' => (int) $gvpId,
                    'team_name' => $this->formatGvpName($gvp),
                    'player_count' => $playerCount,
                    'total_points' => $totalPoints,
                    'team_points' => $totalPoints / $playerCount,
                    'tips_count' => (int) $group->sum(fn (Participant $participant) => (int) $participant->tips_count),
                ];
            })
            ->filter()
            ->sort(function (array $a, array $b) {
                return $b['team_points'] <=> $a['team_points']
                    ?: strcmp($a['team_name'], $b['team_name']);
            })
            ->values()
            ->map(function (array $entry, int $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->toArray();
    }

    /**
     * @return array<int, array{rank: int, gvp_id: int, team_name: string, player_count: int, total_points: int, team_points: float, tips_count: int, evaluated_count: int}>
     */
    public function getTeamRoundLeaderboard(Season $season, string $roundKey): array
    {
        $matchIds = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->forRoundKey($roundKey)
            ->pluck('id');

        if ($matchIds->isEmpty()) {
            return [];
        }

        $participants = $season->participants()
            ->with([
                'user',
                'tips' => fn ($query) => $query->whereIn('match_id', $matchIds),
            ])
            ->get()
            ->filter(function (Participant $participant) {
                return $participant->tips->isNotEmpty();
            });

        return $this->buildTeamLeaderboard(
            $participants,
            function (Participant $participant): int {
                return (int) $participant->tips->sum(fn (Tip $tip) => $tip->points_earned ?? 0);
            },
            fn (Participant $participant): int => $participant->tips->count(),
            fn (Participant $participant): int => $participant->tips->whereNotNull('points_earned')->count(),
        );
    }

    /**
     * @param  Collection<int, Participant>  $participants
     * @param  callable(Participant): int  $pointsResolver
     * @param  callable(Participant): int  $tipsCountResolver
     * @param  (callable(Participant): int)|null  $evaluatedCountResolver
     * @return array<int, array<string, mixed>>
     */
    private function buildTeamLeaderboard(
        Collection $participants,
        callable $pointsResolver,
        callable $tipsCountResolver,
        ?callable $evaluatedCountResolver = null,
    ): array {
        /** @var Collection<int|string|null, Collection<int, Participant>> $grouped */
        $grouped = $participants->groupBy(fn (Participant $participant) => $participant->user?->gvp_id);

        $gvpIds = $grouped->keys()
            ->filter(fn ($gvpId) => $gvpId !== null && $gvpId !== '')
            ->map(fn ($gvpId) => (int) $gvpId)
            ->values();

        if ($gvpIds->isEmpty()) {
            return [];
        }

        $gvpModel = TippspielModels::gvp();
        /** @var Collection<int, Model> $gvps */
        $gvps = $gvpModel::query()->whereIn('id', $gvpIds)->get()->keyBy('id');

        $teams = $grouped
            ->filter(fn (Collection $group, $gvpId) => $gvpId !== null && $gvpId !== '')
            ->map(function (Collection $group, $gvpId) use ($gvps, $pointsResolver, $tipsCountResolver, $evaluatedCountResolver) {
                $playerCount = $group->count();

                if ($playerCount === 0) {
                    return null;
                }

                $totalPoints = (int) $group->sum(fn (Participant $participant) => $pointsResolver($participant));
                $gvp = $gvps->get((int) $gvpId);

                $entry = [
                    'gvp_id' => (int) $gvpId,
                    'team_name' => $this->formatGvpName($gvp),
                    'player_count' => $playerCount,
                    'total_points' => $totalPoints,
                    'team_points' => $totalPoints / $playerCount,
                    'tips_count' => (int) $group->sum(fn (Participant $participant) => $tipsCountResolver($participant)),
                ];

                if ($evaluatedCountResolver !== null) {
                    $entry['evaluated_count'] = (int) $group->sum(fn (Participant $participant) => $evaluatedCountResolver($participant));
                }

                return $entry;
            })
            ->filter()
            ->sort(function (array $a, array $b) {
                return $b['team_points'] <=> $a['team_points']
                    ?: strcmp($a['team_name'], $b['team_name']);
            })
            ->values()
            ->map(function (array $entry, int $index) {
                $entry['rank'] = $index + 1;

                return $entry;
            })
            ->toArray();

        return $teams;
    }

    private function formatGvpName(?Model $gvp): string
    {
        if ($gvp === null) {
            return 'Unbekannt';
        }

        if (isset($gvp->bezeichnung) && is_string($gvp->bezeichnung) && trim($gvp->bezeichnung) !== '') {
            return trim($gvp->bezeichnung);
        }

        return trim(collect([
            $gvp->getAttribute('kuerzel'),
            $gvp->getAttribute('nummer'),
            $gvp->getAttribute('name'),
        ])->filter()->implode(' ')) ?: 'Unbekannt';
    }

    public function pointsBadgeColor(int $points, Season $season): string
    {
        if ($points > 0 && $points === $season->points_exact_result) {
            return 'green';
        }

        if ($points > 0 && $points === $season->points_correct_difference) {
            return 'blue';
        }

        if ($points > 0 && $points === $season->points_correct_tendency) {
            return 'yellow';
        }

        return 'red';
    }

    /**
     * @return Collection<int, array{round_key: string, round_label: string, match_count: int, finished_count: int, is_complete: bool, has_evaluations: bool, round_points_total: int}>
     */
    public function getRoundSummaries(Season $season): Collection
    {
        return $season->availableRounds(tippableOnly: false)
            ->map(function ($round) use ($season) {
                $matches = TippspielMatch::query()
                    ->where('season_id', $season->id)
                    ->forRoundKey($round->key)
                    ->get();

                $matchIds = $matches->pluck('id');
                $finishedCount = $matches->filter(fn (TippspielMatch $match) => $match->isFinished())->count();

                $roundPointsTotal = $matchIds->isEmpty()
                    ? 0
                    : (int) Tip::query()
                        ->whereIn('match_id', $matchIds)
                        ->whereNotNull('points_earned')
                        ->sum('points_earned');

                return [
                    'round_key' => $round->key,
                    'round_label' => $round->label,
                    'match_count' => $matches->count(),
                    'finished_count' => $finishedCount,
                    'is_complete' => $this->isRoundComplete($season, $round->key),
                    'has_evaluations' => $matchIds->isNotEmpty() && Tip::query()
                        ->whereIn('match_id', $matchIds)
                        ->whereNotNull('points_earned')
                        ->exists(),
                    'round_points_total' => $roundPointsTotal,
                ];
            })
            ->reverse()
            ->values();
    }
}
