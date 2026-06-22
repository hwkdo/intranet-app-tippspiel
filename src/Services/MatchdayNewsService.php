<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Services;

use App\Models\News;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatchdayNewsService
{
    public function __construct(
        private readonly TippspielAiNewsPortInterface $aiPort,
        private readonly TipEvaluationService $evaluationService,
    ) {}

    /**
     * Generiert einen KI-News-Artikel für den angegebenen Spieltag und legt ihn als App\Models\News an.
     * Ist die News für diesen Spieltag bereits vorhanden (custom-Marker), wird sie übersprungen.
     */
    public function generateAndPersist(Season $season, int $matchday): ?News
    {
        $settings = TippspielSettings::resolvedAppSettings();

        if (! $settings->aiNewsEnabled) {
            Log::info('Tippspiel: KI-News deaktiviert – kein Artikel erstellt.', [
                'season' => $season->name,
                'matchday' => $matchday,
            ]);

            return null;
        }

        $marker = "tippspiel:{$season->competition_code}:{$matchday}";

        // Idempotenz: bereits existierende News überspringen
        $existing = News::where('custom', $marker)->first();
        if ($existing !== null) {
            Log::info('Tippspiel: News für diesen Spieltag bereits vorhanden.', ['marker' => $marker]);

            return $existing;
        }

        $matchResults = $this->buildMatchResults($season, $matchday);

        if (empty($matchResults)) {
            Log::warning('Tippspiel: Keine abgeschlossenen Spiele für News-Generierung.', [
                'season' => $season->name,
                'matchday' => $matchday,
            ]);

            return null;
        }

        $content = $this->aiPort->generateMatchdayNews($season, $matchday, $matchResults);

        if (! filled($content)) {
            return null;
        }

        // Erste Zeile als Titel extrahieren, Rest als Inhalt
        $lines = explode("\n", trim($content), 2);
        $title = trim($lines[0]);
        $body = isset($lines[1]) ? trim($lines[1]) : $content;

        /** @var int|null $kategorieId */
        $kategorieId = $settings->aiNewsKategorieId > 0 ? $settings->aiNewsKategorieId : null;
        /** @var int|null $publisherId */
        $publisherId = $settings->aiNewsPublisherId > 0 ? $settings->aiNewsPublisherId : null;

        if ($kategorieId === null || $publisherId === null) {
            Log::warning('Tippspiel: aiNewsKategorieId oder aiNewsPublisherId nicht konfiguriert – News wird nicht erstellt.', [
                'kategorie_id' => $kategorieId,
                'publisher_id' => $publisherId,
            ]);

            return null;
        }

        $news = News::create([
            'title' => $title ?: "Tippspiel: {$season->name} – {$matchday}. Spieltag",
            'content' => nl2br(e($body)),
            'short' => Str::limit(strip_tags($body), 200),
            'slug' => Str::slug("tippspiel-{$season->competition_code}-{$matchday}-spieltag-".now()->format('Y')),
            'publisher_id' => $publisherId,
            'kategorie_id' => $kategorieId,
            'is_published' => true,
            'published_at' => now(),
            'is_slider' => false,
            'custom' => $marker,
        ]);

        Log::info('Tippspiel: KI-News erfolgreich erstellt.', [
            'news_id' => $news->id,
            'marker' => $marker,
        ]);

        return $news;
    }

    /**
     * Erstellt die Ergebnis-Daten für den KI-Prompt inkl. bestem Tipper pro Spiel.
     *
     * @return array<int, array{home: string, away: string, homeScore: int, awayScore: int, topTipper: string|null, points: int}>
     */
    private function buildMatchResults(Season $season, int $matchday): array
    {
        $leaderboard = $this->evaluationService->getLeaderboard($season);
        $topParticipantId = $leaderboard[0]['participant_id'] ?? null;

        return \Hwkdo\IntranetAppTippspiel\Models\TippspielMatch::query()
            ->where('season_id', $season->id)
            ->where('matchday', $matchday)
            ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->with(['tips' => fn ($q) => $q->orderByDesc('points_earned')->with('participant.user')])
            ->get()
            ->map(function ($match) {
                $topTip = $match->tips->first();

                return [
                    'home' => $match->home_team_name,
                    'away' => $match->away_team_name,
                    'homeScore' => $match->home_score,
                    'awayScore' => $match->away_score,
                    'topTipper' => $topTip?->participant?->user?->name,
                    'points' => $topTip?->points_earned ?? 0,
                ];
            })
            ->toArray();
    }
}
