<?php

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Livewire\Volt\Component;

new class extends Component {
    public function with(TipEvaluationService $evaluationService): array
    {
        $season = Season::active()->first();
        $leaderboard = $season ? $evaluationService->getLeaderboard($season) : [];

        return [
            'season' => $season,
            'leaderboard' => array_slice($leaderboard, 0, 8),
            'currentUserId' => auth()->id(),
        ];
    }
}; ?>

<div>
    @if ($season)
        <div class="mb-2 flex items-center justify-between">
            <flux:text class="text-sm font-medium">{{ $season->name }}</flux:text>
            <flux:button size="xs" variant="ghost" href="{{ route('apps.tippspiel.rangliste', $season) }}" wire:navigate>
                Alle →
            </flux:button>
        </div>
        @forelse ($leaderboard as $entry)
            <div class="flex items-center justify-between py-1 {{ $entry['user_id'] == $currentUserId ? 'rounded bg-blue-50 px-1 dark:bg-blue-950' : '' }}">
                <div class="flex items-center gap-2">
                    <span class="w-5 text-center text-xs text-zinc-400">{{ $entry['rank'] }}.</span>
                    <span class="text-sm truncate">{{ $entry['user_name'] }}</span>
                </div>
                <span class="text-sm font-bold">{{ $entry['total_points'] }}</span>
            </div>
        @empty
            <flux:text class="text-sm text-zinc-500">Noch keine Punkte.</flux:text>
        @endforelse
    @else
        <flux:text class="text-sm text-zinc-500">Keine aktive Tippspiel-Saison.</flux:text>
    @endif
</div>
