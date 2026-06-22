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
#[Title('Rangliste')]
class Rangliste extends Component
{
    public Season $season;

    public function mount(Season $season): void
    {
        $this->season = $season;
    }

    public function render(TipEvaluationService $evaluationService): View
    {
        $leaderboard = $evaluationService->getLeaderboard($this->season);
        $userId = auth()->id();

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.rangliste', [
            'leaderboard' => $leaderboard,
            'currentUserId' => $userId,
        ]);
    }
}
