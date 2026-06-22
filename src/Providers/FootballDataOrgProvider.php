<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Providers;

use Hwkdo\IntranetAppTippspiel\Contracts\FootballDataProviderInterface;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FootballDataOrgProvider implements FootballDataProviderInterface
{
    private const BASE_URL = 'https://api.football-data.org/v4';

    private const HEADER_REQUESTS_AVAILABLE = 'X-RequestsAvailable';

    private const HEADER_COUNTER_RESET = 'X-RequestCounter-Reset';

    private const MAX_RETRIES = 1;

    private RateLimitState $rateLimitState;

    public function __construct()
    {
        $this->rateLimitState = new RateLimitState;
    }

    public function fetchCompetitionSeasons(string $competitionCode): array
    {
        $response = $this->get("/competitions/{$competitionCode}/seasons");

        return $response->json('seasons', []);
    }

    public function fetchMatches(Season $season): array
    {
        $year = $season->season_year;
        $response = $this->get(
            "/competitions/{$season->competition_code}/matches",
            ['season' => $year]
        );

        return $response->json('matches', []);
    }

    public function fetchMatchdayResults(Season $season, int $matchday): array
    {
        $year = $season->season_year;
        $response = $this->get(
            "/competitions/{$season->competition_code}/matches",
            ['season' => $year, 'matchday' => $matchday]
        );

        return $response->json('matches', []);
    }

    /**
     * Führt einen GET-Request mit Rate-Limit-Behandlung und einmaligem Retry bei 429 aus.
     *
     * @param  array<string, mixed>  $query
     */
    private function get(string $path, array $query = [], int $attempt = 0): Response
    {
        $this->rateLimitState->throttleIfNeeded();

        $response = Http::withHeaders([
            'X-Auth-Token' => config('services.football_data_org.api_key'),
        ])->get(self::BASE_URL.$path, $query);

        $this->updateRateLimitState($response);

        if ($response->status() === 429) {
            if ($attempt < self::MAX_RETRIES) {
                $waitSeconds = $this->rateLimitState->getResetInSeconds() + 1;
                Log::warning('football-data.org: 429 Too Many Requests – warte '.$waitSeconds.' Sekunden.', [
                    'path' => $path,
                ]);
                sleep($waitSeconds);

                return $this->get($path, $query, $attempt + 1);
            }

            $response->throw();
        }

        $response->throw();

        return $response;
    }

    private function updateRateLimitState(Response $response): void
    {
        $available = (int) ($response->header(self::HEADER_REQUESTS_AVAILABLE) ?: 10);
        $reset = (int) ($response->header(self::HEADER_COUNTER_RESET) ?: 60);

        $this->rateLimitState->update($available, $reset);
    }
}
