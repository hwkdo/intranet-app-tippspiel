<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Hwkdo\IntranetAppTippspiel\Models\Season;

final class MatchdayNewsImagePromptBuilder
{
    public const DEFAULT_PROMPT = <<<'PROMPT'
        Erstelle ein professionelles Sport-News-Titelbild für ein internes Firmen-Fußball-Tippspiel im Format 16:9.

        Saison: {season_name}
        Spieltag: {matchday}

        Top-Begegnungen dieses Spieltags:
        {featured_matches}

        Teams (offizielle Wappen werden nach der Generierung unten auf dem Bild platziert):
        {team_names}

        Anforderungen:
        - Saisonname und Spieltagsnummer als gut lesbare Typografie prominent platzieren
        - Unteres Drittel des Bildes frei/unaufdringlich halten — dort werden echte Team-Wappen eingeblendet
        - Dynamischer Sport-Sender-Stil, modern und energetisch, geeignet als News-Header
        - Keine erfundenen Vereinslogos in der KI-Grafik — nur Typografie und Stimmung
        - Keine zusätzlichen Wasserzeichen oder fremden Marken
        PROMPT;

    /**
     * @param  list<array{home: string, away: string, homeScore: int, awayScore: int, homeCrestUrl: string|null, awayCrestUrl: string|null}>  $featuredMatches
     */
    public function build(
        Season $season,
        int $matchday,
        array $featuredMatches,
        ?string $template = null,
    ): string {
        $template = filled($template) ? $template : self::DEFAULT_PROMPT;

        $replacements = [
            '{season_name}' => $season->name,
            '{matchday}' => (string) $matchday,
            '{featured_matches}' => $this->formatFeaturedMatches($featuredMatches),
            '{team_names}' => $this->formatTeamNames($featuredMatches),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @param  list<array{home: string, away: string, homeScore: int, awayScore: int, homeCrestUrl: string|null, awayCrestUrl: string|null}>  $featuredMatches
     */
    private function formatFeaturedMatches(array $featuredMatches): string
    {
        if ($featuredMatches === []) {
            return 'Keine Begegnungen verfügbar.';
        }

        return collect($featuredMatches)
            ->map(fn (array $match) => "- {$match['home']} {$match['homeScore']}:{$match['awayScore']} {$match['away']}")
            ->implode("\n");
    }

    /**
     * @param  list<array{home: string, away: string, homeScore: int, awayScore: int, homeCrestUrl: string|null, awayCrestUrl: string|null}>  $featuredMatches
     */
    private function formatTeamNames(array $featuredMatches): string
    {
        $teams = collect($featuredMatches)
            ->flatMap(fn (array $match) => [$match['home'], $match['away']])
            ->unique()
            ->values();

        if ($teams->isEmpty()) {
            return 'Keine Teams verfügbar.';
        }

        return $teams->implode(', ');
    }
}
