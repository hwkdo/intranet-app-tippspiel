<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Database\Factories;

use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TippspielMatch>
 */
class TippspielMatchFactory extends Factory
{
    protected $model = TippspielMatch::class;

    public function definition(): array
    {
        $teams = ['Bayern München', 'Borussia Dortmund', 'RB Leipzig', 'Bayer Leverkusen', 'Union Berlin', 'Freiburg', 'Wolfsburg', 'Mönchengladbach'];

        return [
            'season_id' => Season::factory(),
            'external_id' => $this->faker->unique()->numberBetween(100000, 999999),
            'matchday' => $this->faker->numberBetween(1, 34),
            'stage' => 'REGULAR_SEASON',
            'group' => null,
            'home_team_name' => $this->faker->randomElement($teams),
            'away_team_name' => $this->faker->randomElement($teams),
            'home_team_crest_url' => null,
            'away_team_crest_url' => null,
            'kickoff_at' => now()->addDays($this->faker->numberBetween(-7, 14)),
            'status' => MatchStatus::Scheduled,
            'home_score' => null,
            'away_score' => null,
            'last_synced_at' => null,
        ];
    }

    public function finished(): static
    {
        return $this->state([
            'status' => MatchStatus::Finished,
            'kickoff_at' => now()->subDays($this->faker->numberBetween(1, 14)),
            'home_score' => $this->faker->numberBetween(0, 5),
            'away_score' => $this->faker->numberBetween(0, 5),
            'last_synced_at' => now(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'status' => MatchStatus::Scheduled,
            'kickoff_at' => now()->addDays($this->faker->numberBetween(1, 14)),
            'home_score' => null,
            'away_score' => null,
        ]);
    }
}
