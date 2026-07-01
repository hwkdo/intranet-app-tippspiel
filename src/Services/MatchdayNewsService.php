<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use App\Models\News;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsPromptBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatchdayNewsService
{
    public function __construct(
        private readonly TippspielAiNewsPortInterface $aiPort,
        private readonly TipEvaluationService $evaluationService,
        private readonly MatchdayNewsContextBuilder $contextBuilder,
        private readonly MatchdayNewsPromptBuilder $promptBuilder,
        private readonly MatchdayNewsImageService $imageService,
    ) {}

    public static function markerFor(Season $season, int $matchday): string
    {
        return "tippspiel:{$season->competition_code}:{$matchday}";
    }

    public function findExistingNews(Season $season, int $matchday): ?News
    {
        return News::query()
            ->where('custom', self::markerFor($season, $matchday))
            ->first();
    }

    public function buildPromptPreview(Season $season, int $matchday, ?string $template = null): ?string
    {
        $context = $this->contextBuilder->build($season, $matchday);

        if ($context === null) {
            return null;
        }

        if ($template === null) {
            $template = TippspielSettings::resolvedAppSettings()->resolvedAiNewsPrompt();
        }

        return $this->promptBuilder->build(
            $context,
            filled($template) ? $template : null,
        );
    }

    /**
     * Erstellt KI-News für alle vollständig abgeschlossenen Spieltage einer Saison, sofern konfiguriert.
     */
    public function autoGenerateForCompletedMatchdays(Season $season): void
    {
        $settings = TippspielSettings::resolvedAppSettings();

        if (! $settings->aiNewsAutoCreateAfterMatchday) {
            return;
        }

        $matchdays = TippspielMatch::query()
            ->where('season_id', $season->id)
            ->whereNotNull('matchday')
            ->distinct()
            ->pluck('matchday');

        foreach ($matchdays as $matchday) {
            if ($this->evaluationService->isMatchdayComplete($season, (int) $matchday)) {
                $this->generateAndPersist($season, (int) $matchday, isAutomatic: true);
            }
        }
    }

    /**
     * Generiert einen KI-News-Artikel für den angegebenen Spieltag und legt ihn als App\Models\News an.
     * Ist die News für diesen Spieltag bereits vorhanden (custom-Marker), wird sie übersprungen.
     */
    public function generateAndPersist(Season $season, int $matchday, bool $isAutomatic = false): ?News
    {
        $settings = TippspielSettings::resolvedAppSettings();

        if ($isAutomatic && ! $settings->aiNewsAutoCreateAfterMatchday) {
            return null;
        }

        if (! $settings->isAiNewsConfigured()) {
            Log::warning('Tippspiel: aiNewsKategorieId oder aiNewsPublisherId nicht konfiguriert – News wird nicht erstellt.', [
                'kategorie_id' => $settings->aiNewsKategorieId,
                'publisher_id' => $settings->aiNewsPublisherId,
                'automatic' => $isAutomatic,
            ]);

            return null;
        }

        $marker = self::markerFor($season, $matchday);

        $existing = News::where('custom', $marker)->first();
        if ($existing !== null) {
            Log::info('Tippspiel: News für diesen Spieltag bereits vorhanden.', ['marker' => $marker]);

            return $existing;
        }

        $context = $this->contextBuilder->build($season, $matchday);

        if ($context === null) {
            Log::warning('Tippspiel: Keine abgeschlossenen Spiele für News-Generierung.', [
                'season' => $season->name,
                'matchday' => $matchday,
            ]);

            return null;
        }

        $prompt = $this->promptBuilder->build($context, $settings->resolvedAiNewsPrompt());

        $content = $this->aiPort->generateMatchdayNews($prompt);

        if (! filled($content)) {
            return null;
        }

        $lines = explode("\n", trim($content), 2);
        $title = trim($lines[0]);
        $body = isset($lines[1]) ? trim($lines[1]) : $content;

        $isPublished = $settings->aiNewsAutoPublish;

        $news = News::create([
            'title' => $title ?: "Tippspiel: {$season->name} – {$matchday}. Spieltag",
            'content' => nl2br(e($body)),
            'short' => Str::limit(strip_tags($body), 200),
            'slug' => Str::slug("tippspiel-{$season->competition_code}-{$matchday}-spieltag-".now()->format('Y')),
            'publisher_id' => $settings->aiNewsPublisherId,
            'kategorie_id' => $settings->aiNewsKategorieId,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
            'is_slider' => false,
            'custom' => $marker,
        ]);

        Log::info('Tippspiel: KI-News erfolgreich erstellt.', [
            'news_id' => $news->id,
            'marker' => $marker,
            'is_published' => $isPublished,
            'automatic' => $isAutomatic,
        ]);

        $this->imageService->generateAndAttach($news, $season, $matchday);

        return $news;
    }
}
