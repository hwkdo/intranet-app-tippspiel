<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Archiv')]
class Archiv extends Component
{
    public function render(TipEvaluationService $evaluationService): View
    {
        $seasons = Season::query()
            ->where('is_active', false)
            ->withCount('participants')
            ->orderByDesc('season_year')
            ->orderBy('name')
            ->get();

        $seasonsData = $seasons->map(function (Season $season) use ($evaluationService) {
            $leaderboard = $evaluationService->getLeaderboard($season);
            $winner = $leaderboard[0] ?? null;

            return [
                'season' => $season,
                'participant_count' => $season->participants_count,
                'winner_name' => $winner['user_name'] ?? null,
                'winner_points' => $winner['total_points'] ?? null,
            ];
        });

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.archiv', [
            'seasonsData' => $seasonsData,
        ]);
    }
}
