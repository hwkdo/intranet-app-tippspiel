<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Deine Tipps abgeben"
>
    {{-- Spieltag-Auswahl --}}
    <div class="glass-card mb-4 p-4">
        <div class="flex items-center gap-3">
            <flux:label>Spieltag</flux:label>
            <flux:select wire:model.live="selectedMatchday" class="w-40">
                @foreach ($matchdays as $matchday)
                    <flux:select.option value="{{ $matchday }}">Spieltag {{ $matchday }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($selectedMatchday)
                <flux:button size="sm" variant="primary" wire:click="saveTips" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveTips">Tipps speichern</span>
                    <span wire:loading wire:target="saveTips">Speichere...</span>
                </flux:button>
            @endif
        </div>
    </div>

    @forelse ($matches as $match)
        @php
            $locked = !$match->isTippable() || $match->kickoffHasPassed();
        @endphp
        <div class="glass-card mb-3 p-4 {{ $locked ? 'opacity-60' : '' }}">
            <div class="flex flex-col items-start gap-3 md:flex-row md:items-center">
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
                <div class="flex flex-1 items-center gap-2">
                    <span class="w-40 truncate text-right font-medium">{{ $match->home_team_name }}</span>

                    @if ($locked)
                        <div class="flex items-center gap-1">
                            <flux:badge color="{{ $match->isFinished() ? 'green' : 'zinc' }}" size="sm">
                                {{ $match->score_display }}
                            </flux:badge>
                        </div>
                        <div class="flex items-center gap-1 text-sm text-zinc-500">
                            Tipp: {{ $tips[$match->id]['home'] ?? '?' }}:{{ $tips[$match->id]['away'] ?? '?' }}
                        </div>
                    @else
                        <flux:input
                            type="number"
                            min="0"
                            max="20"
                            wire:model="tips.{{ $match->id }}.home"
                            class="w-14 text-center"
                            placeholder="0"
                        />
                        <span class="font-bold text-zinc-400">:</span>
                        <flux:input
                            type="number"
                            min="0"
                            max="20"
                            wire:model="tips.{{ $match->id }}.away"
                            class="w-14 text-center"
                            placeholder="0"
                        />
                    @endif

                    <span class="w-40 truncate font-medium">{{ $match->away_team_name }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="glass-card p-8 text-center">
            <flux:text class="text-zinc-500">Kein Spieltag ausgewählt oder keine Spiele gefunden.</flux:text>
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
