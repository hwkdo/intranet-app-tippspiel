<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tippspiel')]
class Dashboard extends Component
{
    public function render(TipEvaluationService $evaluationService): View
    {
        $user = auth()->user();
        $activeSeasons = Season::active();

        $seasonsData = $activeSeasons->map(function (Season $season) use ($user, $evaluationService) {
            $participant = Participant::where('season_id', $season->id)
                ->where('user_id', $user->getAuthIdentifier())
                ->first();

            $leaderboard = $evaluationService->getLeaderboard($season);
            $currentRoundKey = $season->currentRoundKey();
            $currentRoundLabel = $season->availableRounds()
                ->firstWhere('key', $currentRoundKey)?->label;
            $nextUntipped = $participant ? $season->nextUntippedMatch($user->getAuthIdentifier()) : null;
            $upcomingTips = $participant
                ? $this->upcomingTipsForParticipant($participant, $season)
                : collect();

            return [
                'season' => $season,
                'isParticipant' => $participant !== null,
                'participant' => $participant,
                'leaderboard' => array_slice($leaderboard, 0, 5),
                'currentRoundLabel' => $currentRoundLabel,
                'nextUntipped' => $nextUntipped,
                'upcomingTips' => $upcomingTips,
            ];
        });

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.dashboard', [
            'seasonsData' => $seasonsData,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Tip>
     */
    private function upcomingTipsForParticipant(Participant $participant, Season $season): \Illuminate\Support\Collection
    {
        return Tip::query()
            ->where('participant_id', $participant->id)
            ->whereHas('match', function ($query) use ($season) {
                $query->where('season_id', $season->id)
                    ->stillTippable();
            })
            ->with('match')
            ->get()
            ->sortBy(fn (Tip $tip) => $tip->match->kickoff_at?->timestamp ?? PHP_INT_MAX)
            ->take(5)
            ->values();
    }
}
