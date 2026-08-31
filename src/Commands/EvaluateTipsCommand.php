<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Commands;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsService;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Illuminate\Console\Command;

class EvaluateTipsCommand extends Command
{
    protected $signature = 'tippspiel:evaluate-tips
                            {season? : ID der Saison (ohne Angabe: alle aktiven Saisons)}
                            {--matchday= : Nur diesen Spieltag auswerten}
                            {--reevaluate : Bereits ausgewertete Tipps neu berechnen}
                            {--dry-run : Nur anzeigen, nichts speichern}';

    protected $description = 'Wertet Tipps für abgeschlossene Spiele aus und vergibt Punkte';

    public function handle(
        TipEvaluationService $evaluationService,
        MatchdayNewsService $newsService,
    ): int {
        $seasonId = $this->argument('season');
        $matchday = $this->option('matchday') !== null ? (int) $this->option('matchday') : null;
        $reevaluate = (bool) $this->option('reevaluate');
        $dryRun = (bool) $this->option('dry-run');

        if ($seasonId !== null) {
            $seasons = Season::where('id', $seasonId)->get();
        } else {
            $seasons = Season::active();
        }

        if ($seasons->isEmpty()) {
            $this->info('Keine aktiven Saisons gefunden.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY-RUN: Es werden keine Punkte gespeichert.');
        }

        foreach ($seasons as $season) {
            $mode = $reevaluate ? 'Neuauswertung' : 'Auswertung';
            $this->info("{$mode} für Saison: {$season->name} (Exakt={$season->points_exact_result}, Diff={$season->points_correct_difference}, Tendenz={$season->points_correct_tendency})");

            try {
                $result = $evaluationService->evaluateSeason(
                    $season,
                    $matchday,
                    reevaluate: $reevaluate,
                    dryRun: $dryRun,
                );

                $this->info("  → geprüft: {$result['evaluated']}, geändert: {$result['changed']}, unverändert: {$result['unchanged']}, Teilnehmer betroffen: ".count($result['participant_ids']));

                if ($result['changes'] !== []) {
                    $this->table(
                        ['Tipp-ID', 'Match', 'Tipp', 'alt', 'neu'],
                        collect($result['changes'])->map(fn (array $change): array => [
                            $change['tip_id'],
                            $change['match_label'],
                            $change['tip_score'],
                            $change['old_points'] ?? 'null',
                            $change['new_points'],
                        ])->all(),
                    );
                }

                if (! $dryRun) {
                    $newsService->autoGenerateForCompletedMatchdays($season);
                }
            } catch (\Throwable $e) {
                $this->error("  Fehler: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
