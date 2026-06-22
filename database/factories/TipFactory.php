<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Database\Factories;

use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tip>
 */
class TipFactory extends Factory
{
    protected $model = Tip::class;

    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'match_id' => TippspielMatch::factory(),
            'home_score_tip' => $this->faker->numberBetween(0, 5),
            'away_score_tip' => $this->faker->numberBetween(0, 5),
            'points_earned' => null,
        ];
    }

    public function withPoints(int $points): static
    {
        return $this->state(['points_earned' => $points]);
    }
}
