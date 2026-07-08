<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use App\Models\News;
use Hwkdo\IntranetAppBase\Contracts\AiConfigResolverInterface;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppBase\Enums\AiProvider;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsPromptBuilder;
use Hwkdo\IntranetAppTippspiel\Support\RoundKey;
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

    public static function markerFor(Season $season, string $roundKey): string
    {
        return 'tippspiel:'.$season->competition_code.':'.RoundKey::toSlug($roundKey);
    }

    /**
     * @return list<string>
     */
    public static function markerCandidates(Season $season, string $roundKey): array
    {
        $markers = [self::markerFor($season, $roundKey)];

        if (preg_match('/^md:(\d+)$/', $roundKey, $matches) === 1) {
            $markers[] = "tippspiel:{$season->competition_code}:{$matches[1]}";
        }

        return array_values(array_unique($markers));
    }

    public function findExistingNews(Season $season, string $roundKey): ?News
    {
        return News::query()
            ->whereIn('custom', self::markerCandidates($season, $roundKey))
            ->first();
    }

    public function buildPromptPreview(Season $season, string $roundKey, ?string $template = null): ?string
    {
        $context = $this->contextBuilder->build($season, $roundKey);

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
     * Erstellt KI-News für alle vollständig abgeschlossenen Runden einer Saison, sofern konfiguriert.
     */
    public function autoGenerateForCompletedMatchdays(Season $season): void
    {
        $settings = TippspielSettings::resolvedAppSettings();

        if (! $settings->aiNewsAutoCreateAfterMatchday) {
            return;
        }

        foreach ($season->availableRounds(tippableOnly: false) as $round) {
            if ($this->evaluationService->isRoundComplete($season, $round->key)) {
                $this->generateAndPersist($season, $round->key, isAutomatic: true);
            }
        }
    }

    /**
     * Generiert einen KI-News-Artikel für die angegebene Runde und legt ihn als App\Models\News an.
     * Ist die News für diese Runde bereits vorhanden (custom-Marker), wird sie übersprungen.
     */
    public function generateAndPersist(Season $season, string $roundKey, bool $isAutomatic = false): ?News
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $round = $season->availableRounds(tippableOnly: false)->firstWhere('key', $roundKey);
        $roundLabel = $round?->label ?? $roundKey;

        if ($isAutomatic && ! $settings->aiNewsAutoCreateAfterMatchday) {
            return null;
        }

        if (! $settings->isAiNewsConfigured()) {
            Log::warning('Tippspiel: aiNewsKategorieId oder aiNewsPublisherId nicht konfiguriert – News wird nicht erstellt.', [
                'kategorie_id' => $settings->aiNewsKategorieId,
                'publisher_id' => $settings->aiNewsPublisherId,
                'automatic' => $isAutomatic,
                'round_key' => $roundKey,
            ]);

            return null;
        }

        $marker = self::markerFor($season, $roundKey);

        $existing = $this->findExistingNews($season, $roundKey);
        if ($existing !== null) {
            Log::info('Tippspiel: News für diese Runde bereits vorhanden.', ['marker' => $marker]);

            return $existing;
        }

        $context = $this->contextBuilder->build($season, $roundKey);

        if ($context === null) {
            Log::warning('Tippspiel: Keine abgeschlossenen Spiele für News-Generierung.', [
                'season' => $season->name,
                'round_key' => $roundKey,
            ]);

            return null;
        }

        $prompt = $this->promptBuilder->build($context, $settings->resolvedAiNewsPrompt());

        $resolvedAi = app(AiConfigResolverInterface::class)->resolve('tippspiel', AiCapability::Text);

        Log::info('Tippspiel: KI-News-Prompt erstellt, rufe Provider auf.', [
            'season' => $season->name,
            'round_key' => $roundKey,
            'round_label' => $roundLabel,
            'provider' => $resolvedAi->provider->value,
            'model' => $resolvedAi->model,
            'config_source' => $resolvedAi->source->value,
        ]);

        $content = $this->aiPort->generateMatchdayNews($prompt);

        if (! filled($content)) {
            Log::warning('Tippspiel: KI-Provider lieferte keinen Artikeltext.', [
                'season' => $season->name,
                'round_key' => $roundKey,
                'provider' => $resolvedAi->provider->value,
                'model' => $resolvedAi->model,
            ]);

            return null;
        }

        $lines = explode("\n", trim($content), 2);
        $title = trim($lines[0]);
        $body = isset($lines[1]) ? trim($lines[1]) : $content;

        $isPublished = $settings->aiNewsAutoPublish;

        $news = News::create([
            'title' => $title ?: "Tippspiel: {$season->name} – {$roundLabel}",
            'content' => nl2br(e($body)),
            'short' => Str::limit(strip_tags($body), 200),
            'slug' => Str::slug("tippspiel-{$season->competition_code}-".RoundKey::toSlug($roundKey).'-'.now()->format('Y')),
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

        $this->imageService->generateAndAttach($news, $season, $roundKey);

        return $news;
    }

    /**
     * Ersetzt eine vorhandene KI-News für die Runde durch eine neu generierte Version.
     */
    public function regenerateAndPersist(Season $season, string $roundKey): ?News
    {
        $existing = $this->findExistingNews($season, $roundKey);

        if ($existing !== null) {
            Log::info('Tippspiel: Vorhandene KI-News wird für Neu-Generierung gelöscht.', [
                'news_id' => $existing->id,
                'round_key' => $roundKey,
            ]);

            $existing->delete();
        }

        return $this->generateAndPersist($season, $roundKey, isAutomatic: false);
    }

    public function explainGenerationFailure(Season $season, string $roundKey, bool $isAutomatic = false): string
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $round = $season->availableRounds(tippableOnly: false)->firstWhere('key', $roundKey);
        $roundLabel = $round?->label ?? $roundKey;

        if ($isAutomatic && ! $settings->aiNewsAutoCreateAfterMatchday) {
            return 'Automatische KI-News ist in den Einstellungen deaktiviert.';
        }

        if (! $settings->isAiNewsConfigured()) {
            return sprintf(
                'KI-News nicht konfiguriert: Kategorie-ID=%d, Publisher-ID=%d. Bitte unter Tippspiel → Admin → Einstellungen setzen.',
                $settings->aiNewsKategorieId,
                $settings->aiNewsPublisherId,
            );
        }

        if ($this->findExistingNews($season, $roundKey) !== null) {
            return "Für {$roundLabel} existiert bereits ein News-Artikel.";
        }

        if (! $this->evaluationService->isRoundComplete($season, $roundKey)) {
            return "{$roundLabel} ist noch nicht vollständig abgeschlossen.";
        }

        if ($this->contextBuilder->build($season, $roundKey) === null) {
            $total = TippspielMatch::query()
                ->where('season_id', $season->id)
                ->forRoundKey($roundKey)
                ->count();

            $finished = TippspielMatch::query()
                ->where('season_id', $season->id)
                ->forRoundKey($roundKey)
                ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
                ->whereNotNull('home_score')
                ->whereNotNull('away_score')
                ->count();

            return "Keine auswertbaren Ergebnisse für {$roundLabel} ({$finished}/{$total} Spiele abgeschlossen mit Ergebnis). Zuerst Tipps auswerten?";
        }

        $resolvedAi = app(AiConfigResolverInterface::class)->resolve('tippspiel', AiCapability::Text);

        if ($resolvedAi->provider === AiProvider::Langdock && ! filled(config('services.langdock.api_key'))) {
            return 'LANGDOCK_API_KEY fehlt in der Server-Konfiguration (services.langdock.api_key).';
        }

        return 'KI-Provider lieferte keinen Artikeltext. Details in storage/logs/laravel.log (Suche nach „Tippspiel“).';
    }
}
