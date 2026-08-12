<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Hwkdo\IntranetAppTippspiel\Support\TippspielModels;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Saison Archiv')]
class Archiv extends Component
{
    public Season $season;

    public string $wertung = 'einzel';

    public function mount(Season $season): void
    {
        abort_unless($season->isArchived(), 404);

        $this->season = $season;
    }

    public function render(TipEvaluationService $evaluationService): View
    {
        $user = auth()->user();
        $userModel = TippspielModels::user();

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.archiv', [
            'leaderboard' => $evaluationService->getLeaderboard($this->season),
            'teamLeaderboard' => $evaluationService->getTeamLeaderboard($this->season),
            'roundSummaries' => $evaluationService->getRoundSummaries($this->season),
            'currentUserId' => $user?->id,
            'currentUserGvpId' => $user instanceof $userModel ? $user->gvp_id : null,
        ]);
    }
}
