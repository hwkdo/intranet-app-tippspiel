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
                            {--matchday= : Nur diesen Spieltag auswerten}';

    protected $description = 'Wertet Tipps für abgeschlossene Spiele aus und vergibt Punkte';

    public function handle(
        TipEvaluationService $evaluationService,
        MatchdayNewsService $newsService,
    ): int
    {
        $seasonId = $this->argument('season');
        $matchday = $this->option('matchday') !== null ? (int) $this->option('matchday') : null;

        if ($seasonId !== null) {
            $seasons = Season::where('id', $seasonId)->get();
        } else {
            $seasons = Season::active();
        }

        if ($seasons->isEmpty()) {
            $this->info('Keine aktiven Saisons gefunden.');

            return self::SUCCESS;
        }

        foreach ($seasons as $season) {
            $this->info("Werte Tipps aus für Saison: {$season->name}");

            try {
                $count = $evaluationService->evaluateSeason($season, $matchday);
                $this->info("  → {$count} Tipps ausgewertet.");

                $newsService->autoGenerateForCompletedMatchdays($season);
            } catch (\Throwable $e) {
                $this->error("  Fehler: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
