<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Database\Factories;

use App\Models\User;
use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'user_id' => User::factory(),
            'total_points' => 0,
            'registered_at' => now(),
        ];
    }
}
