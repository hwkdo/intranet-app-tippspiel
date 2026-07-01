<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Commands;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsService;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Illuminate\Console\Command;

class GenerateMatchdayNewsCommand extends Command
{
    protected $signature = 'tippspiel:generate-news
                            {season : ID der Saison}
                            {matchday : Spieltag-Nummer}';

    protected $description = 'Generiert einen KI-Newsartikel für den abgeschlossenen Spieltag';

    public function handle(
        MatchdayNewsService $newsService,
        TipEvaluationService $evaluationService,
    ): int {
        $seasonId = (int) $this->argument('season');
        $matchday = (int) $this->argument('matchday');

        $season = Season::find($seasonId);

        if ($season === null) {
            $this->error("Saison mit ID {$seasonId} nicht gefunden.");

            return self::FAILURE;
        }

        if (! $evaluationService->isMatchdayComplete($season, $matchday)) {
            $this->warn("Spieltag {$matchday} ist noch nicht vollständig abgeschlossen.");

            return self::FAILURE;
        }

        $existingNews = $newsService->findExistingNews($season, $matchday);

        $this->info("Generiere News für {$season->name} – Spieltag {$matchday}...");

        try {
            $news = $newsService->generateAndPersist($season, $matchday, isAutomatic: false);

            if ($news === null) {
                $this->warn($newsService->explainGenerationFailure($season, $matchday));

                return self::FAILURE;
            }

            if ($existingNews !== null && $existingNews->id === $news->id) {
                $this->info("News bereits vorhanden: [{$news->id}] {$news->title}");

                return self::SUCCESS;
            }

            $this->info("News erstellt: [{$news->id}] {$news->title}");
        } catch (\Throwable $e) {
            $this->error("Fehler: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
