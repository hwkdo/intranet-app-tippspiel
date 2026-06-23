<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use Hwkdo\IntranetAppTippspiel\Contracts\FootballDataProviderInterface;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FootballDataSyncService
{
    public function __construct(
        private readonly FootballDataProviderInterface $provider,
    ) {}

    /**
     * Importiert alle Spiele einer Saison (initialer Import oder Vollsync).
     * Nutzt einen einzigen API-Call für alle Spieltage.
     */
    public function syncAllMatches(Season $season): int
    {
        $matches = $this->provider->fetchMatches($season);
        $synced = 0;

        DB::transaction(function () use ($season, $matches, &$synced) {
            foreach ($matches as $matchData) {
                $this->upsertMatch($season, $matchData);
                $synced++;
            }
        });

        Log::info('Tippspiel: Vollsync abgeschlossen', [
            'season' => $season->name,
            'matches_synced' => $synced,
        ]);

        return $synced;
    }

    /**
     * Synchronisiert nur den aktuellen und nächsten Spieltag (stündlicher Cron).
     * Überspringt abgeschlossene Matches, um API-Quota zu schonen.
     */
    public function syncCurrentMatchday(Season $season): int
    {
        $currentMatchday = $season->currentMatchday();
        $synced = 0;

        if ($currentMatchday !== null) {
            foreach (array_unique([$currentMatchday, $currentMatchday + 1]) as $matchday) {
                $matches = $this->provider->fetchMatchdayResults($season, $matchday);

                DB::transaction(function () use ($season, $matches, &$synced) {
                    foreach ($matches as $matchData) {
                        $status = MatchStatus::tryFrom($matchData['status'] ?? '') ?? MatchStatus::Scheduled;

                        // Bereits abgeschlossene und bereits als FINISHED gespeicherte Spiele nicht erneut verarbeiten
                        $existing = TippspielMatch::where('external_id', $matchData['id'])->first();
                        if ($existing && $existing->isFinished() && $status->isFinished()) {
                            continue;
                        }

                        $this->upsertMatch($season, $matchData);
                        $synced++;
                    }
                });
            }
        }

        $synced += $this->syncOpenKnockoutStages($season);

        Log::info('Tippspiel: Spieltag-Sync abgeschlossen', [
            'season' => $season->name,
            'matchday' => $currentMatchday,
            'matches_synced' => $synced,
        ]);

        return $synced;
    }

    /**
     * Legt ein Spiel an oder aktualisiert es (Upsert anhand external_id).
     *
     * @param  array<string, mixed>  $matchData
     */
    private function upsertMatch(Season $season, array $matchData): void
    {
        $fullTime = $matchData['score']['fullTime'] ?? [];
        $homeTeam = $matchData['homeTeam'] ?? null;
        $awayTeam = $matchData['awayTeam'] ?? null;

        TippspielMatch::updateOrCreate(
            ['external_id' => $matchData['id']],
            [
                'season_id' => $season->id,
                'matchday' => $matchData['matchday'] ?? null,
                'stage' => $matchData['stage'] ?? 'REGULAR_SEASON',
                'group' => $matchData['group'] ?? null,
                'home_team_name' => $this->resolveTeamName($homeTeam),
                'away_team_name' => $this->resolveTeamName($awayTeam),
                'home_team_crest_url' => $homeTeam['crest'] ?? null,
                'away_team_crest_url' => $awayTeam['crest'] ?? null,
                'kickoff_at' => isset($matchData['utcDate']) ? new \DateTimeImmutable($matchData['utcDate']) : null,
                'status' => $matchData['status'] ?? MatchStatus::Scheduled->value,
                'home_score' => $fullTime['home'] ?? null,
                'away_score' => $fullTime['away'] ?? null,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>|null  $team
     */
    private function resolveTeamName(?array $team): string
    {
        if (! TippspielMatch::teamDataIsKnown($team)) {
            return 'Unbekannt';
        }

        $name = $team['name'] ?? null;

        if ($name === null || trim((string) $name) === '') {
            return 'Unbekannt';
        }

        return (string) $name;
    }

    private function syncOpenKnockoutStages(Season $season): int
    {
        $stages = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->whereNull('matchday')
            ->whereNotIn('status', [
                MatchStatus::Finished->value,
                MatchStatus::Awarded->value,
                MatchStatus::Cancelled->value,
            ])
            ->distinct()
            ->pluck('stage')
            ->filter();

        $synced = 0;

        foreach ($stages as $stage) {
            $matches = $this->provider->fetchStageMatches($season, (string) $stage);

            DB::transaction(function () use ($season, $matches, &$synced) {
                foreach ($matches as $matchData) {
                    $status = MatchStatus::tryFrom($matchData['status'] ?? '') ?? MatchStatus::Scheduled;
                    $existing = TippspielMatch::where('external_id', $matchData['id'])->first();

                    if ($existing && $existing->isFinished() && $status->isFinished()) {
                        continue;
                    }

                    $this->upsertMatch($season, $matchData);
                    $synced++;
                }
            });
        }

        return $synced;
    }
}
