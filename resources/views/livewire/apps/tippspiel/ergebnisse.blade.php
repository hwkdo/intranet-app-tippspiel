<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Ergebnisse und meine Tipps"
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
        </div>
    </div>

    @forelse ($matchesData as $data)
        @php
            $match = $data['match'];
            $myTip = $data['myTip'];
        @endphp
        <div class="glass-card mb-3 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
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

                <div class="flex flex-1 items-center justify-center gap-4">
                    <span class="w-36 truncate text-right font-medium">{{ $match->home_team_name }}</span>
                    <div class="flex items-center gap-2">
                        {{-- Ergebnis --}}
                        <flux:badge
                            color="{{ $match->isFinished() ? 'green' : 'zinc' }}"
                            size="lg"
                        >
                            {{ $match->score_display }}
                        </flux:badge>
                    </div>
                    <span class="w-36 truncate font-medium">{{ $match->away_team_name }}</span>
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
            <flux:text class="text-zinc-500">Kein Spieltag ausgewählt.</flux:text>
        </div>
    @endforelse
</x-intranet-app-tippspiel::tippspiel-layout>
