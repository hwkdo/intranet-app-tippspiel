<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;

final class MatchdayNewsFeaturedMatches
{
    private const int MAX_FEATURED_MATCHES = 3;

    /**
     * @return list<array{home: string, away: string, homeScore: int, awayScore: int, homeCrestUrl: string|null, awayCrestUrl: string|null}>
     */
    public function forMatchday(Season $season, int $matchday): array
    {
        return TippspielMatch::query()
            ->where('season_id', $season->id)
            ->where('matchday', $matchday)
            ->whereIn('status', [MatchStatus::Finished->value, MatchStatus::Awarded->value])
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->withCount('tips')
            ->get()
            ->sort(function (TippspielMatch $a, TippspielMatch $b) {
                $aScore = $this->featuredScore($a);
                $bScore = $this->featuredScore($b);

                return $bScore <=> $aScore ?: $a->kickoff_at <=> $b->kickoff_at;
            })
            ->take(self::MAX_FEATURED_MATCHES)
            ->map(fn (TippspielMatch $match) => [
                'home' => $match->home_team_name,
                'away' => $match->away_team_name,
                'homeScore' => (int) $match->home_score,
                'awayScore' => (int) $match->away_score,
                'homeCrestUrl' => $match->home_team_crest_url,
                'awayCrestUrl' => $match->away_team_crest_url,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{home: string, away: string, homeScore: int, awayScore: int, homeCrestUrl: string|null, awayCrestUrl: string|null}>  $featuredMatches
     * @return list<string>
     */
    public function crestUrls(array $featuredMatches): array
    {
        return collect($featuredMatches)
            ->flatMap(fn (array $match) => array_filter([
                $match['homeCrestUrl'],
                $match['awayCrestUrl'],
            ]))
            ->unique()
            ->values()
            ->all();
    }

    private function featuredScore(TippspielMatch $match): int
    {
        $score = (int) $match->tips_count * 10;

        if (filled($match->home_team_crest_url)) {
            $score += 5;
        }

        if (filled($match->away_team_crest_url)) {
            $score += 5;
        }

        return $score;
    }
}
