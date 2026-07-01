<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsService;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Hwkdo\IntranetAppTippspiel\Support\RoundKey;
use Hwkdo\IntranetAppTippspiel\Support\TippspielModels;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Rundenauswertung')]
class RundenAuswertung extends Component
{
    use AuthorizesRequests;

    public Season $season;

    public string $roundKey;

    public string $roundLabel;

    public string $wertung = 'einzel';

    public ?int $matchday = null;

    public function mount(Season $season, string $roundSlug): void
    {
        $this->season = $season;
        $this->roundKey = RoundKey::fromSlug($roundSlug);

        if (preg_match('/^md:(\d+)$/', $this->roundKey, $matches) === 1) {
            $this->matchday = (int) $matches[1];
        }

        $round = $season->availableRounds(tippableOnly: false)
            ->firstWhere('key', $this->roundKey);

        if ($round === null) {
            abort(404);
        }

        $this->roundLabel = $round->label;
    }

    public function generateNews(
        MatchdayNewsService $newsService,
        TipEvaluationService $evaluationService,
    ): void {
        $this->authorize('manage-app-tippspiel');

        if ($this->matchday === null) {
            $this->dispatch('flash', type: 'warning', message: 'KI-News können nur für Spieltage erstellt werden.');

            return;
        }

        if (! $evaluationService->isMatchdayComplete($this->season, $this->matchday)) {
            $this->dispatch('flash', type: 'warning', message: 'Der Spieltag ist noch nicht vollständig abgeschlossen.');

            return;
        }

        try {
            $news = $newsService->generateAndPersist($this->season, $this->matchday, isAutomatic: false);

            if ($news === null) {
                $this->dispatch('flash', type: 'warning', message: 'News konnte nicht erstellt werden. Bitte Kategorie und Publisher in den Einstellungen konfigurieren.');

                return;
            }

            $status = $news->is_published ? 'veröffentlicht' : 'als Entwurf gespeichert';

            $this->dispatch('flash', type: 'success', message: "News \"{$news->title}\" {$status}.");
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'danger', message: 'Fehler: '.$e->getMessage());
        }
    }

    public function render(
        TipEvaluationService $evaluationService,
        MatchdayNewsService $newsService,
    ): View {
        $matches = TippspielMatch::query()
            ->where('season_id', $this->season->id)
            ->forRoundKey($this->roundKey)
            ->orderBy('kickoff_at')
            ->get();

        $participants = Participant::query()
            ->where('season_id', $this->season->id)
            ->with('user')
            ->get()
            ->sortBy(fn (Participant $participant) => $participant->user?->name ?? '')
            ->values();

        $tips = Tip::query()
            ->whereIn('match_id', $matches->pluck('id'))
            ->whereIn('participant_id', $participants->pluck('id'))
            ->get();

        $tipLookup = [];
        foreach ($tips as $tip) {
            $tipLookup[$tip->participant_id][$tip->match_id] = $tip;
        }

        $leaderboard = $evaluationService->getRoundLeaderboard($this->season, $this->roundKey);
        $user = auth()->user();
        $userModel = TippspielModels::user();

        $existingNews = $this->matchday !== null
            ? $newsService->findExistingNews($this->season, $this->matchday)
            : null;

        $isMatchdayComplete = $this->matchday !== null
            && $evaluationService->isMatchdayComplete($this->season, $this->matchday);

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.runden-auswertung', [
            'matches' => $matches,
            'participants' => $participants,
            'tipLookup' => $tipLookup,
            'leaderboard' => $leaderboard,
            'teamLeaderboard' => $evaluationService->getTeamRoundLeaderboard($this->season, $this->roundKey),
            'currentUserId' => $user?->id,
            'currentUserGvpId' => $user instanceof $userModel ? $user->gvp_id : null,
            'evaluationService' => $evaluationService,
            'existingNews' => $existingNews,
            'isMatchdayComplete' => $isMatchdayComplete,
        ]);
    }
}
