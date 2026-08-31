<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Archiv – Rangliste und Auswertungen"
>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <flux:badge color="zinc" size="sm">Archiv</flux:badge>
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.ewige-rangliste') }}" wire:navigate>
            Ewige Rangliste →
        </flux:button>
    </div>

    <div class="glass-card mb-6 p-4">
        <flux:heading size="lg" class="mb-4">Rangliste</flux:heading>

        <flux:tabs wire:model.live="wertung" class="mb-4">
            <flux:tab name="einzel">Einzelwertung</flux:tab>
            <flux:tab name="team">Teamwertung (Gruppen/Fachbereiche)</flux:tab>
            <flux:tab name="abteilung">Teamwertung (Abteilungen)</flux:tab>
        </flux:tabs>

        <div wire:key="archiv-wertung-{{ $wertung }}">
            @if ($wertung === 'team')
                <flux:text class="mb-3 text-sm text-zinc-500">
                    Team-Punkte = Summe der Einzelpunkte ÷ Anzahl der Tippspiel-Teilnehmer je Gruppe/Fachbereich.
                </flux:text>
                <x-intranet-app-tippspiel::teamwertung-table
                    :leaderboard="$teamLeaderboard"
                    :current-user-gvp-id="$currentUserGvpId"
                    mode="group"
                    team-column-label="Team (Gruppe/FB)"
                    empty-message="Noch keine Teams mit Teilnehmern vorhanden."
                />
            @elseif ($wertung === 'abteilung')
                <flux:text class="mb-3 text-sm text-zinc-500">
                    Team-Punkte = Summe der Einzelpunkte ÷ Anzahl der Tippspiel-Teilnehmer je Abteilung
                    (Gruppen/Fachbereiche zählen zu ihrer direkten Parent-Abteilung).
                </flux:text>
                <x-intranet-app-tippspiel::teamwertung-table
                    :leaderboard="$departmentLeaderboard"
                    :current-user-gvp-id="$currentUserDepartmentGvpId"
                    mode="department"
                    team-column-label="Team (Abteilung)"
                    empty-message="Noch keine Abteilungen mit Teilnehmern vorhanden."
                />
            @else
                <x-intranet-app-tippspiel::einzelwertung-table
                    :leaderboard="$leaderboard"
                    :current-user-id="$currentUserId"
                    empty-message="Noch keine Teilnehmer mit Punkten."
                />
            @endif
        </div>
    </div>

    <div class="glass-card p-4">
        <flux:heading size="lg" class="mb-2">Auswertungen nach Runde</flux:heading>
        <flux:text class="mb-4 text-sm text-zinc-500">
            Übersicht der Runden. Details öffnen die Einzel- und Teamwertung einer Runde.
        </flux:text>

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
