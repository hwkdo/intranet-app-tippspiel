<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
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
}
