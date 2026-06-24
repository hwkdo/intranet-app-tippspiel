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
     * Punkteregeln:
     * - Exaktes Ergebnis:    tipHome == realHome && tipAway == realAway
     * - Richtige Differenz:  (tipHome - tipAway) == (realHome - realAway) aber nicht exakt
     * - Richtige Tendenz:    Richtung (Sieg/Unentschieden/Niederlage) stimmt, aber nicht Differenz
     * - Falsch:              0 Punkte
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

        if ($tipDiff === $realDiff) {
            return $pointsDifference;
        }

        $tipTendency = $this->tendency($tipHome, $tipAway);
        $realTendency = $this->tendency($realHome, $realAway);

        if ($tipTendency === $realTendency) {
            return $pointsTendency;
        }

        return 0;
    }

    /**
     * Wertet alle unevaluierten Tips für abgeschlossene Spiele einer Saison aus.
     * Aktualisiert danach die Gesamtpunkte aller betroffenen Teilnehmer.
     */
    public function evaluateSeason(Season $season, ?int $matchday = null): int
    {
        $query = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->whereHas('tips', fn ($q) => $q->whereNull('points_earned'));

        if ($matchday !== null) {
            $query->where('matchday', $matchday);
        }

        $matches = $query->with(['tips.participant'])->get();
        $affected = 0;
        $participantIds = [];

        DB::transaction(function () use ($season, $matches, &$affected, &$participantIds) {
            foreach ($matches as $match) {
                foreach ($match->tips as $tip) {
                    if ($tip->points_earned !== null) {
                        continue;
                    }

                    $points = $this->calculatePoints(
                        tipHome: $tip->home_score_tip,
                        tipAway: $tip->away_score_tip,
                        realHome: $match->home_score,
                        realAway: $match->away_score,
                        pointsExact: $season->points_exact_result,
                        pointsDifference: $season->points_correct_difference,
                        pointsTendency: $season->points_correct_tendency,
                    );

                    $tip->points_earned = $points;
                    $tip->save();
                    $affected++;
                    $participantIds[] = $tip->participant_id;
                }
            }
        });

        // Gesamtpunkte aller betroffenen Teilnehmer neu berechnen
        $uniqueParticipantIds = array_unique($participantIds);
        foreach ($uniqueParticipantIds as $participantId) {
            $participant = $season->participants()->find($participantId);
            $participant?->recalculateTotalPoints();
        }

        Log::info('Tippspiel: Auswertung abgeschlossen', [
            'season' => $season->name,
            'matchday' => $matchday,
            'tips_evaluated' => $affected,
            'participants_updated' => count($uniqueParticipantIds),
        ]);

        return $affected;
    }

    /**
     * Gibt +1 (Heimsieg), -1 (Auswärtssieg) oder 0 (Unentschieden) zurück.
     */
    private function tendency(int $home, int $away): int
    {
        return $home <=> $away;
    }

    /**
     * Gibt zurück, ob alle Spiele eines Spieltages abgeschlossen sind.
     */
    public function isMatchdayComplete(Season $season, int $matchday): bool
    {
        $total = TippspielMatch::where('season_id', $season->id)
            ->where('matchday', $matchday)
            ->count();

        if ($total === 0) {
            return false;
        }

        $finished = TippspielMatch::where('season_id', $season->id)
            ->where('matchday', $matchday)
            ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
            ->count();

        return $total === $finished;
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
        return match ($points) {
            $season->points_exact_result => 'green',
            $season->points_correct_difference => 'blue',
            $season->points_correct_tendency => 'yellow',
            default => 'red',
        };
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
                    'is_complete' => $matches->isNotEmpty() && $finishedCount === $matches->count(),
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
