<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Data;

use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsImagePromptBuilder;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsPromptBuilder;

class AppSettings extends BaseAppSettings
{
    public function __construct(
        #[Description('KI-News-Provider (langdock oder openwebui)')]
        public string $aiNewsProvider = 'langdock',

        #[Description('Modell für die KI-News-Generierung')]
        public string $aiNewsModel = '',

        #[Description('News automatisch nach Spieltagende erstellen')]
        public bool $aiNewsAutoCreateAfterMatchday = false,

        #[Description('News automatisch veröffentlichen')]
        public bool $aiNewsAutoPublish = false,

        #[Description('Prompt-Vorlage für KI-News (Platzhalter: {matchday}, {season_name}, {match_results}, {round_highlights}, {match_tip_analysis}, {leaderboard_changes}, {current_leaderboard}, {storylines}, {leaderboard})')]
        public string $aiNewsPrompt = '',

        #[Description('Kategorie-ID für automatisch erstellte News (0 = nicht gesetzt)')]
        public int $aiNewsKategorieId = 0,

        #[Description('Publisher-User-ID für automatisch erstellte News (0 = nicht gesetzt)')]
        public int $aiNewsPublisherId = 0,

        #[Description('KI-Titelbild automatisch generieren')]
        public bool $aiNewsImageAutoGenerate = false,

        #[Description('Modell für KI-Titelbilder (z. B. gpt-image-1 oder dall-e-3)')]
        public string $aiNewsImageModel = '',

        #[Description('Prompt-Vorlage für KI-Titelbilder (Platzhalter: {season_name}, {matchday}, {featured_matches}, {team_names})')]
        public string $aiNewsImagePrompt = '',

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
}
