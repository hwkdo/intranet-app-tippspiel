<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tippspiel Admin')]
class Index extends Component
{
    public function render(): View
    {
        $stats = [
            'seasons' => Season::count(),
            'active_seasons' => Season::where('is_active', true)->count(),
        ];

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.admin.index', ['stats' => $stats]);
    }
}
