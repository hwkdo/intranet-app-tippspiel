<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Gesamtrangliste"
>
    <div class="mb-4 flex justify-end">
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.auswertungen', $season) }}" wire:navigate>
            Auswertungen nach Runde →
        </flux:button>
    </div>

    <div class="glass-card p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-12" align="center">#</flux:table.column>
                <flux:table.column>Teilnehmer</flux:table.column>
                <flux:table.column align="end" class="w-20">Tipps</flux:table.column>
                <flux:table.column align="end" class="w-24">Punkte</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($leaderboard as $entry)
                    <flux:table.row class="{{ $entry['user_id'] == $currentUserId ? 'bg-blue-50 dark:bg-blue-950' : '' }}">
                        <flux:table.cell align="center">
                            @if ($entry['rank'] === 1)
                                <flux:icon name="trophy" class="size-4 text-yellow-500" />
                            @elseif ($entry['rank'] === 2)
                                <flux:icon name="trophy" class="size-4 text-zinc-400" />
                            @elseif ($entry['rank'] === 3)
                                <flux:icon name="trophy" class="size-4 text-amber-700" />
                            @else
                                <span class="text-sm text-zinc-500">{{ $entry['rank'] }}.</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                {{ $entry['user_name'] }}
                                @if ($entry['user_id'] == $currentUserId)
                                    <flux:badge size="sm" color="blue">Ich</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell align="end">{{ $entry['tips_count'] }}</flux:table.cell>
                        <flux:table.cell align="end" variant="strong">{{ $entry['total_points'] }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-8 text-center text-zinc-500">
                            Noch keine Teilnehmer mit Punkten.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</x-intranet-app-tippspiel::tippspiel-layout>
