<?php

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielMatch;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $season = Season::active()->first();
        $matches = $season
            ? TippspielMatch::where('season_id', $season->id)
                ->stillTippable()
                ->orderBy('kickoff_at')
                ->limit(5)
                ->get()
            : collect();

        return [
            'season' => $season,
            'matches' => $matches,
        ];
    }
}; ?>

<div>
    @if ($season)
        <div class="mb-2 flex items-center justify-between">
            <flux:text class="text-sm font-medium">{{ $season->name }}</flux:text>
            <flux:button size="xs" variant="ghost" href="{{ route('apps.tippspiel.tippen', $season) }}" wire:navigate>
                Tippen →
            </flux:button>
        </div>
        @forelse ($matches as $match)
            <div class="flex items-center justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                <div class="flex flex-col">
                    <x-intranet-app-tippspiel::match-fixture :match="$match" size="sm" />
                    <span class="text-xs text-zinc-500">{{ $match->kickoff_at?->format('D d.m. H:i') ?? '—' }}</span>
                </div>
                <flux:badge size="sm" color="zinc">{{ $match->roundLabel() }}</flux:badge>
            </div>
        @empty
            <flux:text class="text-sm text-zinc-500">Keine anstehenden Spiele.</flux:text>
        @endforelse
    @else
        <flux:text class="text-sm text-zinc-500">Keine aktive Tippspiel-Saison.</flux:text>
    @endif
</div>
