<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use App\Models\News;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsImagePortInterface;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsFeaturedMatches;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsImagePromptBuilder;
use Illuminate\Support\Facades\Log;

class MatchdayNewsImageService
{
    public function __construct(
        private readonly TippspielAiNewsImagePortInterface $imagePort,
        private readonly MatchdayNewsFeaturedMatches $featuredMatches,
        private readonly MatchdayNewsImagePromptBuilder $promptBuilder,
    ) {}

    public function generateAndAttach(News $news, Season $season, int $matchday): bool
    {
        $settings = TippspielSettings::resolvedAppSettings();

        if (! $settings->aiNewsImageAutoGenerate) {
            return false;
        }

        $matches = $this->featuredMatches->forMatchday($season, $matchday);

        if ($matches === []) {
            Log::warning('Tippspiel: Keine Spiele für Titelbild-Generierung.', [
                'season' => $season->name,
                'matchday' => $matchday,
            ]);

            return false;
        }

        $prompt = $this->promptBuilder->build(
            season: $season,
            matchday: $matchday,
            featuredMatches: $matches,
            template: $settings->resolvedAiNewsImagePrompt(),
        );

        $crestUrls = $this->featuredMatches->crestUrls($matches);
        $imagePath = $this->imagePort->generateTitleImage($prompt, $crestUrls);

        if ($imagePath === null || ! is_file($imagePath)) {
            return false;
        }

        try {
            $news->clearMediaCollection('title');
            $news->addMedia($imagePath)
                ->usingName('title')
                ->withResponsiveImages()
                ->toMediaCollection('title', config('media-library.news_disk'));

            Log::info('Tippspiel: KI-Titelbild angehängt.', [
                'news_id' => $news->id,
                'matchday' => $matchday,
                'crest_count' => count($crestUrls),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Tippspiel: KI-Titelbild konnte nicht gespeichert werden.', [
                'news_id' => $news->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function buildPromptPreview(Season $season, int $matchday, ?string $template = null): ?string
    {
        $matches = $this->featuredMatches->forMatchday($season, $matchday);

        if ($matches === []) {
            return null;
        }

        return $this->promptBuilder->build(
            season: $season,
            matchday: $matchday,
            featuredMatches: $matches,
            template: $template,
        );
    }
}
