<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Flux\Flux;
use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsService;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Hwkdo\IntranetAppTippspiel\Support\RoundKey;
use Hwkdo\IntranetAppTippspiel\Support\TippspielModels;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
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

    public bool $isGeneratingNews = false;

    public bool $isRegeneratingNews = false;

    public function mount(Season $season, string $roundSlug): void
    {
        $this->season = $season;
        $this->roundKey = RoundKey::fromSlug($roundSlug);

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

        if (! $evaluationService->isRoundComplete($this->season, $this->roundKey)) {
            Flux::toast(
                heading: 'KI-News',
                text: 'Die Runde ist noch nicht vollständig abgeschlossen.',
                variant: 'warning',
            );

            return;
        }

        Log::info('Tippspiel: Manuelle KI-News-Generierung gestartet.', [
            'season_id' => $this->season->id,
            'round_key' => $this->roundKey,
            'user_id' => auth()->id(),
        ]);

        $this->isGeneratingNews = true;

        try {
            $news = $newsService->generateAndPersist($this->season, $this->roundKey, isAutomatic: false);

            if ($news === null) {
                Flux::toast(
                    heading: 'KI-News fehlgeschlagen',
                    text: $newsService->explainGenerationFailure($this->season, $this->roundKey),
                    variant: 'warning',
                );

                return;
            }

            $status = $news->is_published ? 'veröffentlicht' : 'als Entwurf gespeichert';
            $settings = TippspielSettings::resolvedAppSettings();
            $imageMissing = $settings->aiNewsImageAutoGenerate && ! $news->fresh()->hasTitleImage();

            Flux::toast(
                heading: $imageMissing ? 'KI-News erstellt (ohne Titelbild)' : 'KI-News erstellt',
                text: $imageMissing
                    ? "\"{$news->title}\" {$status}. Das KI-Titelbild konnte nicht erzeugt werden – Details in storage/logs/laravel.log."
                    : "\"{$news->title}\" {$status}.",
                variant: $imageMissing ? 'warning' : 'success',
            );
        } catch (\Throwable $e) {
            Log::error('Tippspiel: Manuelle KI-News-Generierung fehlgeschlagen.', [
                'season_id' => $this->season->id,
                'round_key' => $this->roundKey,
                'error' => $e->getMessage(),
            ]);

            Flux::toast(
                heading: 'KI-News Fehler',
                text: $e->getMessage(),
                variant: 'danger',
            );
        } finally {
            $this->isGeneratingNews = false;
        }
    }

    public function regenerateNews(
        MatchdayNewsService $newsService,
        TipEvaluationService $evaluationService,
    ): void {
        $this->authorize('manage-app-tippspiel');

        if (! $evaluationService->isRoundComplete($this->season, $this->roundKey)) {
            Flux::toast(
                heading: 'KI-News',
                text: 'Die Runde ist noch nicht vollständig abgeschlossen.',
                variant: 'warning',
            );

            return;
        }

        Log::info('Tippspiel: Manuelle KI-News-Neu-Generierung gestartet.', [
            'season_id' => $this->season->id,
            'round_key' => $this->roundKey,
            'user_id' => auth()->id(),
        ]);

        $this->isRegeneratingNews = true;

        try {
            $news = $newsService->regenerateAndPersist($this->season, $this->roundKey);

            if ($news === null) {
                Flux::toast(
                    heading: 'KI-News fehlgeschlagen',
                    text: $newsService->explainGenerationFailure($this->season, $this->roundKey),
                    variant: 'warning',
                );

                return;
            }

            $status = $news->is_published ? 'veröffentlicht' : 'als Entwurf gespeichert';
            $settings = TippspielSettings::resolvedAppSettings();
            $imageMissing = $settings->aiNewsImageAutoGenerate && ! $news->fresh()->hasTitleImage();

            Flux::toast(
                heading: $imageMissing ? 'KI-News neu generiert (ohne Titelbild)' : 'KI-News neu generiert',
                text: $imageMissing
                    ? "\"{$news->title}\" {$status}. Das KI-Titelbild konnte nicht erzeugt werden – Details in storage/logs/laravel.log."
                    : "\"{$news->title}\" {$status}.",
                variant: $imageMissing ? 'warning' : 'success',
            );
        } catch (\Throwable $e) {
            Log::error('Tippspiel: Manuelle KI-News-Neu-Generierung fehlgeschlagen.', [
                'season_id' => $this->season->id,
                'round_key' => $this->roundKey,
                'error' => $e->getMessage(),
            ]);

            Flux::toast(
                heading: 'KI-News Fehler',
                text: $e->getMessage(),
                variant: 'danger',
            );
        } finally {
            $this->isRegeneratingNews = false;
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

        $existingNews = $newsService->findExistingNews($this->season, $this->roundKey);
        $isRoundComplete = $evaluationService->isRoundComplete($this->season, $this->roundKey);

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
            'isRoundComplete' => $isRoundComplete,
        ]);
    }
}
