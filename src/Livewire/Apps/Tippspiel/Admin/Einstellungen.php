<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin;

use Hwkdo\IntranetAppTippspiel\Data\AppSettings;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tippspiel Einstellungen')]
class Einstellungen extends Component
{
    public string $aiNewsProvider = 'langdock';

    public string $aiNewsModel = '';

    public bool $aiNewsEnabled = false;

    public int $aiNewsKategorieId = 0;

    public int $aiNewsPublisherId = 0;

    public int $defaultPointsExactResult = 3;

    public int $defaultPointsCorrectDifference = 2;

    public int $defaultPointsCorrectTendency = 1;

    public function mount(): void
    {
        $settings = TippspielSettings::resolvedAppSettings();

        $this->aiNewsProvider = $settings->aiNewsProvider;
        $this->aiNewsModel = $settings->aiNewsModel;
        $this->aiNewsEnabled = $settings->aiNewsEnabled;
        $this->aiNewsKategorieId = $settings->aiNewsKategorieId;
        $this->aiNewsPublisherId = $settings->aiNewsPublisherId;
        $this->defaultPointsExactResult = $settings->defaultPointsExactResult;
        $this->defaultPointsCorrectDifference = $settings->defaultPointsCorrectDifference;
        $this->defaultPointsCorrectTendency = $settings->defaultPointsCorrectTendency;
    }

    public function save(): void
    {
        $this->validate([
            'aiNewsProvider' => 'required|in:langdock,openwebui',
            'aiNewsModel' => 'nullable|string|max:100',
            'aiNewsEnabled' => 'boolean',
            'aiNewsKategorieId' => 'integer|min:0',
            'aiNewsPublisherId' => 'integer|min:0',
            'defaultPointsExactResult' => 'required|integer|min:0|max:10',
            'defaultPointsCorrectDifference' => 'required|integer|min:0|max:10',
            'defaultPointsCorrectTendency' => 'required|integer|min:0|max:10',
        ]);

        $settings = new AppSettings(
            aiNewsProvider: $this->aiNewsProvider,
            aiNewsModel: $this->aiNewsModel,
            aiNewsEnabled: $this->aiNewsEnabled,
            aiNewsKategorieId: $this->aiNewsKategorieId,
            aiNewsPublisherId: $this->aiNewsPublisherId,
            defaultPointsExactResult: $this->defaultPointsExactResult,
            defaultPointsCorrectDifference: $this->defaultPointsCorrectDifference,
            defaultPointsCorrectTendency: $this->defaultPointsCorrectTendency,
        );

        TippspielSettings::create([
            'settings' => $settings,
        ]);

        $this->dispatch('flash', type: 'success', message: 'Einstellungen gespeichert.');
    }

    public function render(): View
    {
        return view('intranet-app-tippspiel::livewire.apps.tippspiel.admin.einstellungen');
    }
}
