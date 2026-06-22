<x-intranet-app-tippspiel::tippspiel-layout
    heading="Tippspiel"
    subheading="Tippe auf Bundesliga-Spiele und messe dich mit deinen Kollegen"
>
    @forelse ($seasonsData as $data)
        <div class="glass-card mb-6 p-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">{{ $data['season']->name }}</flux:heading>
                    @if ($data['currentMatchday'])
                        <flux:subheading>Spieltag {{ $data['currentMatchday'] }}</flux:subheading>
                    @endif
                </div>
                <div class="flex gap-2">
                    @if ($data['isParticipant'])
                        <flux:badge color="green">Angemeldet</flux:badge>
                    @else
                        <form action="{{ route('apps.tippspiel.tippen', $data['season']) }}" method="GET">
                            <flux:button size="sm" variant="primary" href="{{ route('apps.tippspiel.tippen', $data['season']) }}" wire:navigate>
                                Jetzt anmelden & tippen
                            </flux:button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($data['nextUntipped'])
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950">
                    <div class="flex items-center gap-2">
                        <flux:icon name="exclamation-triangle" class="size-4 text-amber-600 dark:text-red-400" />
                        <span class="text-sm font-medium text-amber-800 dark:text-red-400">
                            Noch nicht getippt:
                            {{ $data['nextUntipped']->home_team_name }} – {{ $data['nextUntipped']->away_team_name }}
                            ({{ $data['nextUntipped']->kickoff_at?->format('d.m. H:i') }} Uhr)
                        </span>
                        <flux:button size="xs" variant="primary" href="{{ route('apps.tippspiel.tippen', $data['season']) }}" wire:navigate class="ml-auto shrink-0">
                            Jetzt tippen
                        </flux:button>
                    </div>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                {{-- Rangliste Vorschau --}}
                <div>
                    <flux:heading size="sm" class="mb-2">Top 5 Rangliste</flux:heading>
                    @forelse ($data['leaderboard'] as $entry)
                        <div class="flex items-center justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <div class="flex items-center gap-2">
                                <span class="w-5 text-center text-sm font-bold text-zinc-500">{{ $entry['rank'] }}.</span>
                                <span class="text-sm">{{ $entry['user_name'] }}</span>
                            </div>
                            <flux:badge color="{{ $entry['rank'] === 1 ? 'yellow' : 'zinc' }}" size="sm">
                                {{ $entry['total_points'] }} Pkt
                            </flux:badge>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500 text-sm">Noch keine Punkte vergeben.</flux:text>
                    @endforelse

                    @if (count($data['leaderboard']) > 0)
                        <div class="mt-2">
                            <flux:button size="xs" variant="ghost" href="{{ route('apps.tippspiel.rangliste', $data['season']) }}" wire:navigate>
                                Vollständige Rangliste →
                            </flux:button>
                        </div>
                    @endif
                </div>

                {{-- Meine Punkte --}}
                @if ($data['isParticipant'])
                    <div>
                        <flux:heading size="sm" class="mb-2">Meine Statistik</flux:heading>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Meine Punkte</span>
                                <span class="font-bold">{{ $data['participant']->total_points }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Meine Tipps</span>
                                <span>{{ $data['participant']->tips()->count() }}</span>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <flux:button size="sm" href="{{ route('apps.tippspiel.tippen', $data['season']) }}" wire:navigate>
                                Tippen
                            </flux:button>
                            <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.ergebnisse', $data['season']) }}" wire:navigate>
                                Ergebnisse
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>

            @if ($data['isParticipant'] && $data['upcomingTips']->isNotEmpty())
                <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:heading size="sm" class="mb-2">Meine nächsten Tipps</flux:heading>
                    <div class="space-y-2">
                        @foreach ($data['upcomingTips'] as $tip)
                            @php $match = $tip->match; @endphp
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-100 px-3 py-2 dark:border-zinc-800">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium truncate">
                                        {{ $match->home_team_name }} – {{ $match->away_team_name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $match->kickoff_at?->format('D d.m. H:i') ?? '—' }} Uhr
                                        @if ($match->matchday)
                                            · Spieltag {{ $match->matchday }}
                                        @endif
                                    </div>
                                </div>
                                <flux:badge color="blue" size="sm" class="shrink-0">
                                    Tipp: {{ $tip->score_display }}
                                </flux:badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="glass-card p-8 text-center">
            <flux:icon name="trophy" class="mx-auto mb-3 size-12 text-zinc-300" />
            <flux:heading>Keine aktiven Saisons</flux:heading>
            <flux:text class="text-zinc-500">Es gibt derzeit keine aktiven Tippspiel-Saisons.</flux:text>
        </div>
    @endforelse
</x-intranet-app-tippspiel::tippspiel-layout>
