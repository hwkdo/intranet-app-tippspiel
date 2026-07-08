<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin;

use Flux\Flux;
use Hwkdo\IntranetAppTippspiel\Data\AppSettings;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Einstellungen extends Component
{
    public int $defaultPointsExactResult = 3;

    public int $defaultPointsCorrectDifference = 2;

    public int $defaultPointsCorrectTendency = 1;

    public function mount(): void
    {
        $settings = TippspielSettings::resolvedAppSettings();

        $this->defaultPointsExactResult = $settings->defaultPointsExactResult;
        $this->defaultPointsCorrectDifference = $settings->defaultPointsCorrectDifference;
        $this->defaultPointsCorrectTendency = $settings->defaultPointsCorrectTendency;
    }

    public function save(): void
    {
        $this->validate([
            'defaultPointsExactResult' => 'required|integer|min:0|max:10',
            'defaultPointsCorrectDifference' => 'required|integer|min:0|max:10',
            'defaultPointsCorrectTendency' => 'required|integer|min:0|max:10',
        ]);

        $current = TippspielSettings::resolvedAppSettings();

        $settings = AppSettings::from(array_merge($current->toArray(), [
            'defaultPointsExactResult' => $this->defaultPointsExactResult,
            'defaultPointsCorrectDifference' => $this->defaultPointsCorrectDifference,
            'defaultPointsCorrectTendency' => $this->defaultPointsCorrectTendency,
        ]));

        TippspielSettings::persistAppSettings($settings);

        Flux::toast(
            heading: 'Gespeichert',
            text: 'Einstellungen wurden gespeichert.',
            variant: 'success',
        );
    }

    public function render(): View
    {
        return view('intranet-app-tippspiel::livewire.apps.tippspiel.admin.einstellungen');
    }
}
