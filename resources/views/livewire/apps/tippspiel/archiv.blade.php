<x-intranet-app-tippspiel::tippspiel-layout
    heading="Archiv"
    subheading="Vergangene Saisons – Rangliste und Auswertungen"
>
    <div class="mb-4 flex justify-end">
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.ewige-rangliste') }}" wire:navigate>
            Ewige Rangliste →
        </flux:button>
    </div>

    @forelse ($seasonsData as $data)
        <div class="glass-card mb-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <flux:heading size="lg">{{ $data['season']->name }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        Saison {{ $data['season']->season_year }}
                        · {{ $data['participant_count'] }} Teilnehmer
                        @if ($data['winner_name'])
                            · Sieger: {{ $data['winner_name'] }}
                            ({{ $data['winner_points'] }} Pkt)
                        @endif
                    </flux:text>
                </div>
                <flux:badge color="zinc" size="sm">Archiv</flux:badge>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <flux:button size="sm" href="{{ route('apps.tippspiel.rangliste', $data['season']) }}" wire:navigate>
                    Rangliste
                </flux:button>
                <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.auswertungen', $data['season']) }}" wire:navigate>
                    Auswertungen
                </flux:button>
            </div>
        </div>
    @empty
        <div class="glass-card p-8 text-center">
            <flux:icon name="archive-box" class="mx-auto mb-3 size-12 text-zinc-300" />
            <flux:heading>Kein Archiv</flux:heading>
            <flux:text class="text-zinc-500">Es gibt noch keine deaktivierten Saisons.</flux:text>
        </div>
    @endforelse
</x-intranet-app-tippspiel::tippspiel-layout>
