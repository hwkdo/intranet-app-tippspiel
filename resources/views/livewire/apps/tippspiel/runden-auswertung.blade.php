<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="{{ $roundLabel }} — Auswertung"
>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.auswertungen', $season) }}" wire:navigate>
            ← Alle Runden
        </flux:button>
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.ergebnisse', $season) }}" wire:navigate>
            Meine Ergebnisse
        </flux:button>
    </div>

  {{-- Punktelogik --}}
    <div class="glass-card mb-4 p-4">
        <flux:heading size="sm" class="mb-2">Punktevergabe</flux:heading>
        <div class="flex flex-wrap gap-2 text-sm">
            <flux:badge color="green">{{ $season->points_exact_result }} Pkt — exaktes Ergebnis</flux:badge>
            <flux:badge color="blue">{{ $season->points_correct_difference }} Pkt — richtige Tordifferenz</flux:badge>
            <flux:badge color="yellow">{{ $season->points_correct_tendency }} Pkt — richtige Tendenz</flux:badge>
            <flux:badge color="red">0 Pkt — falsch</flux:badge>
        </div>
    </div>

    {{-- Spieltags-Rangliste --}}
    <div class="glass-card mb-4 p-4">
        <flux:heading size="sm" class="mb-3">Rangliste {{ $roundLabel }}</flux:heading>
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
                        <flux:table.cell align="end">
                            {{ $entry['evaluated_count'] }}/{{ $entry['tips_count'] }}
                        </flux:table.cell>
                        <flux:table.cell align="end" variant="strong">{{ $entry['round_points'] }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="py-8 text-center text-zinc-500">
                            Für diese Runde liegen noch keine Tipps vor.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Alle Tipps --}}
    <div class="glass-card p-4">
        <flux:heading size="sm" class="mb-3">Alle Tipps</flux:heading>

        @if ($matches->isEmpty())
            <flux:text class="text-zinc-500">Keine Spiele in dieser Runde.</flux:text>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="sticky left-0 z-10 bg-white px-3 py-2 text-left font-medium dark:bg-zinc-900">Teilnehmer</th>
                            @foreach ($matches as $match)
                                <th class="min-w-[7rem] px-2 py-2 text-center font-medium">
                                    <div class="text-xs text-zinc-500">{{ $match->kickoff_at?->format('d.m.') }}</div>
                                    <div class="truncate" title="{{ $match->home_team_name }} vs. {{ $match->away_team_name }}">
                                        {{ \Illuminate\Support\Str::limit($match->home_team_name, 8) }}
                                    </div>
                                    <div class="text-xs font-normal text-zinc-500">vs</div>
                                    <div class="truncate" title="{{ $match->away_team_name }}">
                                        {{ \Illuminate\Support\Str::limit($match->away_team_name, 8) }}
                                    </div>
                                    @if ($match->isFinished())
                                        <flux:badge color="green" size="sm" class="mt-1">{{ $match->score_display }}</flux:badge>
                                    @endif
                                </th>
                            @endforeach
                            <th class="px-3 py-2 text-right font-medium">Σ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php
                                $rowPoints = 0;
                                $hasAnyTip = false;
                            @endphp
                            <tr class="border-b border-zinc-100 dark:border-zinc-800 {{ $participant->user_id == $currentUserId ? 'bg-blue-50/50 dark:bg-blue-950/30' : '' }}">
                                <td class="sticky left-0 z-10 bg-inherit px-3 py-2 font-medium whitespace-nowrap">
                                    {{ $participant->user?->name ?? 'Unbekannt' }}
                                </td>
                                @foreach ($matches as $match)
                                    @php
                                        $tip = $tipLookup[$participant->id][$match->id] ?? null;
                                        if ($tip !== null) {
                                            $hasAnyTip = true;
                                        }
                                        if ($tip?->points_earned !== null) {
                                            $rowPoints += $tip->points_earned;
                                        }
                                    @endphp
                                    <td class="px-2 py-2 text-center align-middle">
                                        @if ($tip)
                                            <div class="font-medium">{{ $tip->score_display }}</div>
                                            @if ($tip->points_earned !== null)
                                                <flux:badge
                                                    color="{{ $evaluationService->pointsBadgeColor($tip->points_earned, $season) }}"
                                                    size="sm"
                                                    class="mt-0.5"
                                                >
                                                    {{ $tip->points_earned }}
                                                </flux:badge>
                                            @elseif ($match->isFinished())
                                                <flux:badge color="zinc" size="sm" class="mt-0.5">…</flux:badge>
                                            @endif
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right font-bold">
                                    {{ $hasAnyTip ? $rowPoints : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-intranet-app-tippspiel::tippspiel-layout>
