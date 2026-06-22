<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use OpenAI;

class OpenWebUiNewsPort implements TippspielAiNewsPortInterface
{
    public function generateMatchdayNews(Season $season, int $matchday, array $matchResults): string
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $model = filled($settings->aiNewsModel)
            ? $settings->aiNewsModel
            : (string) config('openwebui-api-laravel.default_model', 'gpt-oss:20b');

        $prompt = $this->buildPrompt($season, $matchday, $matchResults);

        $client = OpenAI::factory()
            ->withApiKey((string) config('openwebui-api-laravel.api_key'))
            ->withBaseUri((string) config('openwebui-api-laravel.base_api_url'))
            ->make();

        $result = $client->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 1000,
        ]);

        return $result->choices[0]->message->content ?? '';
    }

    /**
     * @param  array<int, array{home: string, away: string, homeScore: int, awayScore: int, topTipper: string|null, points: int}>  $matchResults
     */
    private function buildPrompt(Season $season, int $matchday, array $matchResults): string
    {
        $resultsText = collect($matchResults)
            ->map(fn ($r) => "- {$r['home']} {$r['homeScore']}:{$r['awayScore']} {$r['away']}"
                .($r['topTipper'] ? " (Bester Tipper: {$r['topTipper']} mit {$r['points']} Punkten)" : ''))
            ->implode("\n");

        return <<<PROMPT
        Du bist Redakteur eines internen Firmen-Intranets. Schreibe einen kurzen, unterhaltsamen News-Artikel (maximal 300 Wörter) über den abgeschlossenen {$matchday}. Spieltag der Saison "{$season->name}" im Tippspiel.

        Spielergebnisse:
        {$resultsText}

        Der Artikel soll:
        - Auf Deutsch verfasst sein
        - Die Highlights des Spieltages kurz zusammenfassen
        - Einen lockeren, sportlichen Ton haben
        - Keinen HTML-Code enthalten, nur Fließtext mit Absätzen
        - Mit einer passenden Überschrift beginnen (als erste Zeile, ohne Markdown-Formatierung)
        PROMPT;
    }
}
