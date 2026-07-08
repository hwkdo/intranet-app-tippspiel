<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Data;

use Hwkdo\IntranetAppBase\Contracts\HasAiSettings;
use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;
use Hwkdo\IntranetAppBase\Enums\AiProvider;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsImagePromptBuilder;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsPromptBuilder;

class AppSettings extends BaseAppSettings implements HasAiSettings
{
    public function __construct(
        #[Description('KI-News-Provider (veraltet — nur noch für bestehende JSON-Daten)')]
        public string $aiNewsProvider = '',

        #[Description('Modell für die KI-News-Generierung (veraltet — nur noch für bestehende JSON-Daten)')]
        public string $aiNewsModel = '',

        #[Description('News automatisch nach Spieltagende erstellen')]
        public bool $aiNewsAutoCreateAfterMatchday = false,

        #[Description('News automatisch veröffentlichen')]
        public bool $aiNewsAutoPublish = false,

        #[Description('Prompt-Vorlage für KI-News (Platzhalter: {matchday}, {round_label}, {season_name}, {match_results}, {round_highlights}, {match_tip_analysis}, {leaderboard_changes}, {current_leaderboard}, {storylines}, {leaderboard})')]
        public string $aiNewsPrompt = '',

        #[Description('Kategorie-ID für automatisch erstellte News (0 = nicht gesetzt)')]
        public int $aiNewsKategorieId = 0,

        #[Description('Publisher-User-ID für automatisch erstellte News (0 = nicht gesetzt)')]
        public int $aiNewsPublisherId = 0,

        #[Description('KI-Titelbild automatisch generieren')]
        public bool $aiNewsImageAutoGenerate = false,

        #[Description('Modell für KI-Titelbilder (veraltet — nur noch für bestehende JSON-Daten)')]
        public string $aiNewsImageModel = '',

        #[Description('Prompt-Vorlage für KI-Titelbilder (Platzhalter: {season_name}, {matchday}, {round_label}, {featured_matches}, {team_names})')]
        public string $aiNewsImagePrompt = '',

        #[Description('KI-Text-Provider überschreiben (leer = Intranet-Base-Default)')]
        public ?AiProvider $aiTextProviderOverride = null,

        #[Description('KI-Text-Modell überschreiben (leer = Base- bzw. Provider-Default)')]
        public ?string $aiTextModelOverride = null,

        #[Description('KI-Bild-Provider überschreiben (leer = Intranet-Base-Default)')]
        public ?AiProvider $aiImageProviderOverride = null,

        #[Description('KI-Bild-Modell überschreiben (leer = Base- bzw. Provider-Default)')]
        public ?string $aiImageModelOverride = null,

        #[Description('Standard-Punkte für exaktes Ergebnis')]
        public int $defaultPointsExactResult = 3,

        #[Description('Standard-Punkte für richtige Tordifferenz')]
        public int $defaultPointsCorrectDifference = 2,

        #[Description('Standard-Punkte für richtige Tendenz')]
        public int $defaultPointsCorrectTendency = 1,
    ) {}

    public function resolvedAiNewsPrompt(): string
    {
        return filled($this->aiNewsPrompt) ? $this->aiNewsPrompt : MatchdayNewsPromptBuilder::DEFAULT_PROMPT;
    }

    public function resolvedAiNewsImagePrompt(): string
    {
        return filled($this->aiNewsImagePrompt) ? $this->aiNewsImagePrompt : MatchdayNewsImagePromptBuilder::DEFAULT_PROMPT;
    }

    public function isAiNewsConfigured(): bool
    {
        return $this->aiNewsKategorieId > 0 && $this->aiNewsPublisherId > 0;
    }

    public function textProviderOverride(): ?AiProvider
    {
        if ($this->aiTextProviderOverride !== null) {
            return $this->aiTextProviderOverride;
        }

        return $this->legacyTextProvider();
    }

    public function textModelOverride(): ?string
    {
        $override = $this->normalizedOverrideString($this->aiTextModelOverride);
        if ($override !== null) {
            return $override;
        }

        return $this->legacyTextModel();
    }

    public function imageProviderOverride(): ?AiProvider
    {
        return $this->aiImageProviderOverride;
    }

    public function imageModelOverride(): ?string
    {
        $override = $this->normalizedOverrideString($this->aiImageModelOverride);
        if ($override !== null) {
            return $override;
        }

        return $this->legacyImageModel();
    }

    /**
     * Liest veraltete Felder für die einmalige Anzeige in der Admin-UI.
     */
    public function legacyTextProvider(): ?AiProvider
    {
        return AiProvider::tryFrom($this->aiNewsProvider);
    }

    public function legacyTextModel(): ?string
    {
        return $this->normalizedOverrideString($this->aiNewsModel);
    }

    public function legacyImageModel(): ?string
    {
        return $this->normalizedOverrideString($this->aiNewsImageModel);
    }

    private function normalizedOverrideString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
