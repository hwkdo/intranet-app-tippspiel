<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Ergebnisse')]
class Ergebnisse extends Component
{
    public Season $season;

    public ?string $selectedRound = null;

    public function mount(Season $season): void
    {
        $this->season = $season;
        $this->selectedRound = $season->defaultRoundKey(tippableOnly: false);
    }

    public function render(): View
    {
        $rounds = $this->season->availableRounds(tippableOnly: false);

        $userId = auth()->id();
        $participant = Participant::where('season_id', $this->season->id)
            ->where('user_id', $userId)
            ->first();

        $matches = collect();
        if ($this->selectedRound !== null) {
            $matches = TippspielMatch::query()
                ->where('season_id', $this->season->id)
                ->forRoundKey($this->selectedRound)
                ->orderBy('kickoff_at')
                ->get()
                ->map(function (TippspielMatch $match) use ($participant) {
                    $myTip = $participant
                        ? Tip::where('participant_id', $participant->id)->where('match_id', $match->id)->first()
                        : null;

                    return [
                        'match' => $match,
                        'myTip' => $myTip,
                    ];
                });
        }

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.ergebnisse', [
            'rounds' => $rounds,
            'matchesData' => $matches,
        ]);
    }
}
