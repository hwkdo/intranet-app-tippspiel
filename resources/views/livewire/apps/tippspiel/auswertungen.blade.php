<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Auswertungen nach Runde"
>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <flux:text class="text-sm text-zinc-500">
            Wähle eine Runde für Einzel- und Teamwertung mit allen Tipps.
        </flux:text>
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.rangliste', $season) }}" wire:navigate>
            Gesamtrangliste
        </flux:button>
    </div>

    <div class="glass-card p-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Runde</flux:table.column>
                <flux:table.column align="end" class="w-36">Spiele</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end" class="w-36">Vergebene Punkte</flux:table.column>
                <flux:table.column align="end" class="w-28"></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($roundSummaries as $summary)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $summary['round_label'] }}</flux:table.cell>
                        <flux:table.cell align="end">
                            {{ $summary['finished_count'] }}/{{ $summary['match_count'] }} beendet
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($summary['has_evaluations'])
                                <flux:badge color="green" size="sm">Ausgewertet</flux:badge>
                            @elseif ($summary['is_complete'])
                                <flux:badge color="amber" size="sm">Wird ausgewertet</flux:badge>
                            @elseif ($summary['finished_count'] > 0)
                                <flux:badge color="blue" size="sm">Teilweise beendet</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Offen</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" variant="strong">
                            @if ($summary['has_evaluations'])
                                {{ $summary['round_points_total'] }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button
                                size="xs"
                                variant="ghost"
                                href="{{ \Hwkdo\IntranetAppTippspiel\Support\RoundKey::route($season, $summary['round_key']) }}"
                                wire:navigate
                            >
                                Details →
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-8 text-center text-zinc-500">
                            Noch keine Runden vorhanden.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</x-intranet-app-tippspiel::tippspiel-layout>
