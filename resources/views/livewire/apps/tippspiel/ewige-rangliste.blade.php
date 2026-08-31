<x-intranet-app-tippspiel::tippspiel-layout
    heading="Ewige Rangliste"
    subheading="Saisonübergreifende Gesamtwertung aller jemals erzielten Punkte"
>
    <div class="glass-card p-4">
        <flux:tabs wire:model.live="wertung" class="mb-4">
            <flux:tab name="einzel">Einzelwertung</flux:tab>
            <flux:tab name="team">Teamwertung (Gruppen/Fachbereiche)</flux:tab>
            <flux:tab name="abteilung">Teamwertung (Abteilungen)</flux:tab>
        </flux:tabs>

        <div wire:key="ewige-wertung-{{ $wertung }}">
            @if ($wertung === 'team')
                <flux:text class="mb-3 text-sm text-zinc-500">
                    Team-Punkte = Summe aller Einzelpunkte über alle Saisons ÷ Anzahl eindeutiger Tippspiel-Teilnehmer je Gruppe/Fachbereich.
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
                    Team-Punkte = Summe aller Einzelpunkte über alle Saisons ÷ Anzahl eindeutiger Tippspiel-Teilnehmer je Abteilung
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
                <flux:text class="mb-3 text-sm text-zinc-500">
                    Punkte sind die Summe aller Saison-Ergebnisse. „Saisons“ zählt, in wie vielen Saisons der Teilnehmer mitgespielt hat.
                </flux:text>
                <x-intranet-app-tippspiel::einzelwertung-table
                    :leaderboard="$leaderboard"
                    :current-user-id="$currentUserId"
                    :show-seasons="true"
                    empty-message="Noch keine Teilnehmer mit Punkten."
                />
            @endif
        </div>
    </div>
</x-intranet-app-tippspiel::tippspiel-layout>
