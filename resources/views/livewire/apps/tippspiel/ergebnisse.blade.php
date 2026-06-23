<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Ergebnisse und meine Tipps"
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
        </div>
    </div>

    @forelse ($matchesData as $data)
        @php
            $match = $data['match'];
            $myTip = $data['myTip'];
        @endphp
        <div class="glass-card mb-3 p-4">
            <div class="flex w-full flex-col gap-3 md:flex-row md:items-center">
                <div class="w-28 shrink-0 text-sm text-zinc-500">
                    {{ $match->kickoff_at?->format('D d.m. H:i') ?? '—' }}
                    <div class="mt-1">
                        @if ($match->isFinished())
                            <flux:badge color="green" size="sm">Beendet</flux:badge>
                        @elseif ($match->status?->isActive())
                            <flux:badge color="red" size="sm">Läuft</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Geplant</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <div class="flex min-w-0 flex-1 justify-end">
                        <x-intranet-app-tippspiel::team
                            :name="$match->home_team_name"
                            :crest="$match->home_team_crest_url"
                            side="home"
                        />
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        {{-- Ergebnis --}}
                        <flux:badge
                            color="{{ $match->isFinished() ? 'green' : 'zinc' }}"
                            size="lg"
                        >
                            {{ $match->score_display }}
                        </flux:badge>
                    </div>
                    <div class="flex min-w-0 flex-1">
                        <x-intranet-app-tippspiel::team
                            :name="$match->away_team_name"
                            :crest="$match->away_team_crest_url"
                            side="away"
                        />
                    </div>
                </div>

                {{-- Mein Tipp und Punkte --}}
                <div class="w-40 shrink-0 text-right">
                    @if ($myTip)
                        <div class="text-sm text-zinc-500">Mein Tipp: <span class="font-medium text-zinc-900 dark:text-white">{{ $myTip->score_display }}</span></div>
                        @if ($myTip->points_earned !== null)
                            @php
                                $color = match($myTip->points_earned) {
                                    3 => 'green',
                                    2 => 'blue',
                                    1 => 'yellow',
                                    default => 'red',
                                };
                            @endphp
                            <flux:badge color="{{ $color }}" size="sm" class="mt-1">
                                {{ $myTip->points_earned }} {{ $myTip->points_earned === 1 ? 'Punkt' : 'Punkte' }}
                            </flux:badge>
                        @elseif ($match->isFinished())
                            <flux:badge color="zinc" size="sm" class="mt-1">Wird ausgewertet</flux:badge>
                        @endif
                    @else
                        <flux:text class="text-sm text-zinc-400">Kein Tipp</flux:text>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="glass-card p-8 text-center">
            <flux:text class="text-zinc-500">Keine Runde ausgewählt.</flux:text>
        </div>
    @endforelse
</x-intranet-app-tippspiel::tippspiel-layout>
