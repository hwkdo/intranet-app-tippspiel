<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use GuzzleHttp\Client as GuzzleClient;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use OpenAI;

class LangdockNewsPort implements TippspielAiNewsPortInterface
{
    public function generateMatchdayNews(Season $season, int $matchday, array $matchResults): string
    {
        $model = config('intranet-app-tippspiel-settings.ai_news_model', 'gpt-4o');
        $prompt = $this->buildPrompt($season, $matchday, $matchResults);

        $client = OpenAI::factory()
            ->withApiKey((string) config('services.langdock.api_key'))
            ->withHttpClient(new GuzzleClient(['timeout' => 60, 'connect_timeout' => 10]))
            ->withBaseUri($this->langdockBaseUri())
            ->make();

        $result = $client->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 1000,
        ]);

        return $result->choices[0]->message->content ?? '';
    }

    private function langdockBaseUri(): string
    {
        $base = rtrim((string) config('services.langdock.base_api_url', 'https://api.langdock.com/'), '/');

        return str_ends_with($base, '/openai/eu/v1') ? $base : $base.'/openai/eu/v1';
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
