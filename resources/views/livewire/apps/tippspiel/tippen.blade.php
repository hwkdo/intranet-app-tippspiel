<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Deine Tipps abgeben"
>
    {{-- Spieltag-Auswahl --}}
    <div class="glass-card mb-4 p-4">
        <div class="flex items-center gap-3">
            <flux:label>Runde</flux:label>
            <flux:select wire:model.live="selectedRound" class="w-56">
                @foreach ($rounds as $round)
                    <flux:select.option value="{{ $round->key }}">{{ $round->label }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($selectedRound)
                <flux:button size="sm" variant="primary" wire:click="saveTips" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveTips">Tipps speichern</span>
                    <span wire:loading wire:target="saveTips">Speichere...</span>
                </flux:button>
            @endif
        </div>
    </div>

    @forelse ($matches as $match)
        @php
            $locked = ! $match->canStillBeTipped();
        @endphp
        <div class="glass-card mb-3 p-4 {{ $locked ? 'opacity-60' : '' }}">
            <div class="flex w-full flex-col gap-3 md:flex-row md:items-center">
                {{-- Kick-off Zeit --}}
                <div class="w-28 shrink-0 text-sm text-zinc-500">
                    {{ $match->kickoff_at?->format('D d.m. H:i') ?? '—' }}
                    @if ($locked)
                        <div>
                            <flux:badge size="sm" color="red">Gesperrt</flux:badge>
                        </div>
                    @endif
                </div>

                {{-- Teams und Tipp-Eingabe --}}
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <div class="flex min-w-0 flex-1 justify-end">
                        <x-intranet-app-tippspiel::team
                            :name="$match->home_team_name"
                            :crest="$match->home_team_crest_url"
                            side="home"
                        />
                    </div>

                    @if ($locked)
                        <div class="flex shrink-0 items-center gap-2">
                            <flux:badge
                                color="{{ $match->isFinished() ? 'green' : 'zinc' }}"
                                size="sm"
                            >
                                {{ $match->score_display }}
                            </flux:badge>
                            <span class="text-sm text-zinc-500">
                                Tipp: {{ $tips[$match->id]['home'] ?? '?' }}:{{ $tips[$match->id]['away'] ?? '?' }}
                            </span>
                        </div>
                    @else
                        <div class="flex shrink-0 items-center gap-1">
                            <input
                                type="number"
                                min="0"
                                max="20"
                                wire:model="tips.{{ $match->id }}.home"
                                data-flux-control
                                class="h-8 w-11 shrink-0 rounded-lg border border-zinc-200 bg-white px-1 text-center text-sm text-zinc-700 shadow-xs dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
                            />
                            <span class="font-bold text-zinc-400">:</span>
                            <input
                                type="number"
                                min="0"
                                max="20"
                                wire:model="tips.{{ $match->id }}.away"
                                data-flux-control
                                class="h-8 w-11 shrink-0 rounded-lg border border-zinc-200 bg-white px-1 text-center text-sm text-zinc-700 shadow-xs dark:border-white/10 dark:bg-white/10 dark:text-zinc-300"
                            />
                        </div>
                    @endif

                    <div class="flex min-w-0 flex-1">
                        <x-intranet-app-tippspiel::team
                            :name="$match->away_team_name"
                            :crest="$match->away_team_crest_url"
                            side="away"
                        />
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="glass-card p-8 text-center">
            <flux:text class="text-zinc-500">Keine Runde ausgewählt oder keine Spiele gefunden.</flux:text>
        </div>
    @endforelse

    @if ($matches->isNotEmpty())
        <div class="mt-4 flex justify-end">
            <flux:button variant="primary" wire:click="saveTips" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveTips">Tipps speichern</span>
                <span wire:loading wire:target="saveTips">Speichere...</span>
            </flux:button>
        </div>
    @endif
</x-intranet-app-tippspiel::tippspiel-layout>
