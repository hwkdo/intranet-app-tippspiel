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

        @can('manage-app-tippspiel')
            @if ($matchday !== null)
                @if ($existingNews)
                    <flux:button
                        size="sm"
                        variant="outline"
                        href="{{ route('news.edit', $existingNews) }}"
                        wire:navigate
                    >
                        News bearbeiten
                        @if (! $existingNews->is_published)
                            <flux:badge color="yellow" size="sm" class="ml-2">Entwurf</flux:badge>
                        @endif
                    </flux:button>
                @elseif ($isMatchdayComplete)
                    <flux:button
                        size="sm"
                        variant="primary"
                        wire:click="generateNews"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="generateNews">KI-News erstellen</span>
                        <span wire:loading wire:target="generateNews">Wird erstellt…</span>
                    </flux:button>
                @else
                    <flux:badge color="zinc">Spieltag noch nicht abgeschlossen</flux:badge>
                @endif
            @endif
        @endcan
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

    {{-- Wertung --}}
    <div class="glass-card mb-4 p-4">
        <flux:heading size="sm" class="mb-3">Wertung {{ $roundLabel }}</flux:heading>

        <flux:tabs wire:model.live="wertung" class="mb-4">
            <flux:tab name="einzel">Einzelwertung</flux:tab>
            <flux:tab name="team">Teamwertung</flux:tab>
        </flux:tabs>

        @if ($wertung === 'team')
            <flux:text class="mb-3 text-sm text-zinc-500">
                Team-Punkte = Summe der Einzelpunkte ÷ Anzahl der Tippspiel-Teilnehmer je GVP-Einheit.
            </flux:text>
            <x-intranet-app-tippspiel::teamwertung-table
                :leaderboard="$teamLeaderboard"
                :current-user-gvp-id="$currentUserGvpId"
                empty-message="Für diese Runde liegen noch keine Team-Tipps vor."
            />
        @else
            <x-intranet-app-tippspiel::einzelwertung-table
                :leaderboard="$leaderboard"
                :current-user-id="$currentUserId"
                empty-message="Für diese Runde liegen noch keine Tipps vor."
            />
        @endif
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
