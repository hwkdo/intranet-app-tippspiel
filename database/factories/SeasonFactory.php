<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Database\Factories;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => 'Bundesliga '.$this->faker->year(),
            'competition_code' => 'BL1',
            'external_id' => $this->faker->unique()->numberBetween(2000, 9999),
            'season_year' => $this->faker->year(),
            'is_active' => false,
            'points_exact_result' => 3,
            'points_correct_difference' => 2,
            'points_correct_tendency' => 1,
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->endOfYear(),
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }
}
