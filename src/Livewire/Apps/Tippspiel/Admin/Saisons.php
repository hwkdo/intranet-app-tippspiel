<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin;

use Hwkdo\IntranetAppTippspiel\Contracts\FootballDataProviderInterface;
use Hwkdo\IntranetAppTippspiel\Data\AppSettings;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Services\FootballDataSyncService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tippspiel Saisons')]
class Saisons extends Component
{
    public bool $showImportModal = false;

    public string $importCompetitionCode = 'BL1';

    public int $importSeasonYear = 0;

    public ?string $importError = null;

    public function mount(): void
    {
        $this->importSeasonYear = (int) now()->format('Y');
    }

    public function toggleActive(int $seasonId): void
    {
        $season = Season::findOrFail($seasonId);
        $season->is_active = ! $season->is_active;
        $season->save();

        $this->dispatch('flash', type: 'success', message: "Saison \"{$season->name}\" wurde ".($season->is_active ? 'aktiviert' : 'deaktiviert').'.');
    }

    public function importSeason(FootballDataProviderInterface $provider, FootballDataSyncService $syncService): void
    {
        $this->validate([
            'importCompetitionCode' => 'required|string|max:10',
            'importSeasonYear' => 'required|integer|min:2000|max:2099',
        ]);

        $this->importError = null;

        try {
            $settings = TippspielSettings::resolvedAppSettings();

            $season = Season::firstOrCreate(
                ['competition_code' => $this->importCompetitionCode, 'season_year' => $this->importSeasonYear],
                [
                    'name' => strtoupper($this->importCompetitionCode).' '.$this->importSeasonYear,
                    'is_active' => false,
                    'points_exact_result' => $settings->defaultPointsExactResult,
                    'points_correct_difference' => $settings->defaultPointsCorrectDifference,
                    'points_correct_tendency' => $settings->defaultPointsCorrectTendency,
                ]
            );

            $count = $syncService->syncAllMatches($season);

            $this->showImportModal = false;
            $this->dispatch('flash', type: 'success', message: "Saison \"{$season->name}\" importiert – {$count} Spiele.");
        } catch (\Throwable $e) {
            $this->importError = $e->getMessage();
        }
    }

    public function updatePoints(int $seasonId, int $exact, int $difference, int $tendency): void
    {
        $season = Season::findOrFail($seasonId);
        $season->update([
            'points_exact_result' => max(0, $exact),
            'points_correct_difference' => max(0, $difference),
            'points_correct_tendency' => max(0, $tendency),
        ]);

        $this->dispatch('flash', type: 'success', message: 'Punkte-Regeln gespeichert.');
    }

    public function syncSeason(int $seasonId, FootballDataSyncService $syncService): void
    {
        $season = Season::findOrFail($seasonId);

        try {
            $count = $syncService->syncCurrentMatchday($season);
            $this->dispatch('flash', type: 'success', message: "{$count} Spiele synchronisiert.");
        } catch (\Throwable $e) {
            $this->dispatch('flash', type: 'danger', message: 'Fehler: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        $seasons = Season::orderByDesc('season_year')->orderBy('competition_code')->get();

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.admin.saisons', [
            'seasons' => $seasons,
        ]);
    }
}
