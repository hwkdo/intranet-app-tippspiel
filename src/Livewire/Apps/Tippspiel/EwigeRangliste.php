<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Hwkdo\IntranetAppTippspiel\Support\TippspielModels;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Ewige Rangliste')]
class EwigeRangliste extends Component
{
    public string $wertung = 'einzel';

    public function render(TipEvaluationService $evaluationService): View
    {
        $user = auth()->user();
        $userModel = TippspielModels::user();

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.ewige-rangliste', [
            'leaderboard' => $evaluationService->getAllTimeLeaderboard(),
            'teamLeaderboard' => $evaluationService->getAllTimeTeamLeaderboard(),
            'departmentLeaderboard' => $evaluationService->getAllTimeDepartmentLeaderboard(),
            'currentUserId' => $user?->id,
            'currentUserGvpId' => $user instanceof $userModel ? $user->gvp_id : null,
            'currentUserDepartmentGvpId' => $user instanceof $userModel
                ? $evaluationService->resolveDepartmentGvpIdForUser($user)
                : null,
        ]);
    }
}
