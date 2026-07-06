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

    public function generateAndAttach(News $news, Season $season, string $roundKey): bool
    {
        $settings = TippspielSettings::resolvedAppSettings();

        if (! $settings->aiNewsImageAutoGenerate) {
            return false;
        }

        $round = $season->availableRounds(tippableOnly: false)->firstWhere('key', $roundKey);
        $roundLabel = $round?->label ?? $roundKey;
        $matches = $this->featuredMatches->forRound($season, $roundKey);

        if ($matches === []) {
            Log::warning('Tippspiel: Keine Spiele für Titelbild-Generierung.', [
                'season' => $season->name,
                'round_key' => $roundKey,
            ]);

            return false;
        }

        $prompt = $this->promptBuilder->build(
            season: $season,
            roundLabel: $roundLabel,
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
                'round_key' => $roundKey,
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

    public function buildPromptPreview(Season $season, string $roundKey, ?string $template = null): ?string
    {
        $round = $season->availableRounds(tippableOnly: false)->firstWhere('key', $roundKey);
        $roundLabel = $round?->label ?? $roundKey;
        $matches = $this->featuredMatches->forRound($season, $roundKey);

        if ($matches === []) {
            return null;
        }

        return $this->promptBuilder->build(
            season: $season,
            roundLabel: $roundLabel,
            featuredMatches: $matches,
            template: $template,
        );
    }
}
