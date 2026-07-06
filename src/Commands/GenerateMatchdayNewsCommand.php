<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Commands;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsService;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Hwkdo\IntranetAppTippspiel\Support\RoundKey;
use Illuminate\Console\Command;

class GenerateMatchdayNewsCommand extends Command
{
    protected $signature = 'tippspiel:generate-news
                            {season : ID der Saison}
                            {round : Runden-Slug (z. B. md-1 oder stage-LAST_32)}';

    protected $description = 'Generiert einen KI-Newsartikel für die abgeschlossene Runde';

    public function handle(
        MatchdayNewsService $newsService,
        TipEvaluationService $evaluationService,
    ): int {
        $seasonId = (int) $this->argument('season');
        $roundSlug = (string) $this->argument('round');

        if (ctype_digit($roundSlug)) {
            $roundSlug = 'md-'.$roundSlug;
        }

        try {
            $roundKey = RoundKey::fromSlug($roundSlug);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $season = Season::find($seasonId);

        if ($season === null) {
            $this->error("Saison mit ID {$seasonId} nicht gefunden.");

            return self::FAILURE;
        }

        $round = $season->availableRounds(tippableOnly: false)->firstWhere('key', $roundKey);

        if ($round === null) {
            $this->error("Runde „{$roundSlug}“ existiert nicht in Saison „{$season->name}“.");

            return self::FAILURE;
        }

        if (! $evaluationService->isRoundComplete($season, $roundKey)) {
            $this->warn("{$round->label} ist noch nicht vollständig abgeschlossen.");

            return self::FAILURE;
        }

        $existingNews = $newsService->findExistingNews($season, $roundKey);

        $this->info("Generiere News für {$season->name} – {$round->label}...");

        try {
            $news = $newsService->generateAndPersist($season, $roundKey, isAutomatic: false);

            if ($news === null) {
                $this->warn($newsService->explainGenerationFailure($season, $roundKey));

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
