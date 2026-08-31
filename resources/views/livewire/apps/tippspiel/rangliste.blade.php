<x-intranet-app-tippspiel::tippspiel-layout
    heading="{{ $season->name }}"
    subheading="Gesamtwertung"
>
    <div class="mb-4 flex justify-end">
        <flux:button size="sm" variant="ghost" href="{{ route('apps.tippspiel.auswertungen', $season) }}" wire:navigate>
            Auswertungen nach Runde →
        </flux:button>
    </div>

    <div class="glass-card p-4">
        <flux:tabs wire:model.live="wertung" class="mb-4">
            <flux:tab name="einzel">Einzelwertung</flux:tab>
            <flux:tab name="team">Teamwertung (Gruppen/Fachbereiche)</flux:tab>
            <flux:tab name="abteilung">Teamwertung (Abteilungen)</flux:tab>
        </flux:tabs>

        @if ($wertung === 'team')
            <flux:text class="mb-3 text-sm text-zinc-500">
                Team-Punkte = Summe der Einzelpunkte ÷ Anzahl der Tippspiel-Teilnehmer je Gruppe/Fachbereich.
            </flux:text>
            <x-intranet-app-tippspiel::teamwertung-table
                :leaderboard="$teamLeaderboard"
                :current-user-gvp-id="$currentUserGvpId"
                team-column-label="Team (Gruppe/FB)"
                empty-message="Noch keine Teams mit Teilnehmern vorhanden."
            />
        @elseif ($wertung === 'abteilung')
            <flux:text class="mb-3 text-sm text-zinc-500">
                Team-Punkte = Summe der Einzelpunkte ÷ Anzahl der Tippspiel-Teilnehmer je Abteilung
                (Gruppen/Fachbereiche zählen zu ihrer direkten Parent-Abteilung; Nutzer direkt auf einer Abteilung zählen mit).
            </flux:text>
            <x-intranet-app-tippspiel::teamwertung-table
                :leaderboard="$departmentLeaderboard"
                :current-user-gvp-id="$currentUserDepartmentGvpId"
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
</x-intranet-app-tippspiel::tippspiel-layout>
