<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel;

use Flux\Flux;
use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\Tip;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tippen')]
class Tippen extends Component
{
    public Season $season;

    public ?string $selectedRound = null;

    /** @var array<int, array{home: string, away: string}> */
    public array $tips = [];

    public function mount(Season $season): void
    {
        $this->season = $season;
        $this->selectedRound = $season->defaultRoundKey(tippableOnly: true);
        $this->loadTips();
    }

    public function updatedSelectedRound(): void
    {
        $this->loadTips();
    }

    private function loadTips(): void
    {
        if ($this->selectedRound === null) {
            return;
        }

        $userId = auth()->id();
        $participant = Participant::where('season_id', $this->season->id)
            ->where('user_id', $userId)
            ->first();

        $matches = TippspielMatch::query()
            ->where('season_id', $this->season->id)
            ->forRoundKey($this->selectedRound)
            ->withKnownTeams()
            ->orderBy('kickoff_at')
            ->get();

        $this->tips = [];

        foreach ($matches as $match) {
            $existingTip = $participant
                ? Tip::where('participant_id', $participant->id)->where('match_id', $match->id)->first()
                : null;

            $this->tips[$match->id] = [
                'home' => (string) ($existingTip?->home_score_tip ?? ''),
                'away' => (string) ($existingTip?->away_score_tip ?? ''),
            ];
        }
    }

    public function saveTips(): void
    {
        $userId = auth()->id();

        // Automatische Registrierung als Teilnehmer beim ersten Tipp
        $participant = Participant::firstOrCreate(
            ['season_id' => $this->season->id, 'user_id' => $userId],
            ['registered_at' => now()]
        );

        $saved = 0;
        $skipped = 0;

        foreach ($this->tips as $matchId => $scores) {
            $match = TippspielMatch::find($matchId);

            if ($match === null) {
                continue;
            }

            // Tipps nach Anpfiff, ohne feststehende Paarung oder für laufende/beendete Spiele sperren
            if (! $match->canStillBeTipped()) {
                $skipped++;
                continue;
            }

            $homeScore = $scores['home'];
            $awayScore = $scores['away'];

            if (! is_numeric($homeScore) || ! is_numeric($awayScore)) {
                continue;
            }

            Tip::updateOrCreate(
                ['participant_id' => $participant->id, 'match_id' => $matchId],
                [
                    'home_score_tip' => (int) $homeScore,
                    'away_score_tip' => (int) $awayScore,
                    'points_earned' => null,
                ]
            );
            $saved++;
        }

        if ($saved === 0 && $skipped === 0) {
            Flux::toast(
                heading: 'Keine Tipps gespeichert',
                text: 'Bitte für mindestens ein Spiel ein Ergebnis eintragen.',
                variant: 'warning',
            );

            return;
        }

        if ($skipped > 0) {
            Flux::toast(
                heading: 'Tipps teilweise gespeichert',
                text: "{$saved} Tipp(s) gespeichert. {$skipped} Spiel(e) bereits gestartet – Tipps nicht möglich.",
                variant: 'warning',
            );
        } else {
            Flux::toast(
                heading: 'Tipps gespeichert',
                text: "{$saved} Tipp(s) erfolgreich gespeichert.",
                variant: 'success',
            );
        }
    }

    public function render(): View
    {
        $rounds = $this->season->availableRounds(tippableOnly: true);

        $matches = $this->selectedRound !== null
            ? TippspielMatch::query()
                ->where('season_id', $this->season->id)
                ->forRoundKey($this->selectedRound)
                ->withKnownTeams()
                ->orderBy('kickoff_at')
                ->get()
            : collect();

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.tippen', [
            'rounds' => $rounds,
            'matches' => $matches,
        ]);
    }
}
