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
#[Title('Auswertungen')]
class Auswertungen extends Component
{
    public Season $season;

    public function mount(Season $season): void
    {
        $this->season = $season;
    }

    public function render(TipEvaluationService $evaluationService): View
    {
        return view('intranet-app-tippspiel::livewire.apps.tippspiel.auswertungen', [
            'roundSummaries' => $evaluationService->getRoundSummaries($this->season),
            'currentUserId' => auth()->id(),
        ]);
    }
}
