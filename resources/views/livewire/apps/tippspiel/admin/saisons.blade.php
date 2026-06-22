<x-intranet-app-tippspiel::tippspiel-layout
    heading="Saisons verwalten"
    subheading="Wettbewerbe importieren und aktivieren"
>
    <div class="mb-4 flex justify-end">
        <flux:button variant="primary" wire:click="$set('showImportModal', true)">
            <flux:icon name="plus" class="size-4" />
            Saison importieren
        </flux:button>
    </div>

    {{-- Import Modal --}}
    <flux:modal wire:model="showImportModal" class="md:w-96">
        <flux:heading>Saison importieren</flux:heading>
        <flux:text class="mb-4 text-zinc-500">Spieldaten von football-data.org laden.</flux:text>

        <div class="space-y-4">
            <flux:field>
                <flux:label>Wettbewerb-Code</flux:label>
                <flux:select wire:model="importCompetitionCode">
                    <flux:select.option value="BL1">Bundesliga (BL1)</flux:select.option>
                    <flux:select.option value="BL2">2. Bundesliga (BL2)</flux:select.option>
                    <flux:select.option value="WC">FIFA World Cup (WC)</flux:select.option>
                    <flux:select.option value="EC">European Championship (EC)</flux:select.option>
                    <flux:select.option value="CL">UEFA Champions League (CL)</flux:select.option>
                    <flux:select.option value="PL">Premier League (PL)</flux:select.option>
                </flux:select>
                <flux:text class="text-xs text-zinc-500">Weitere Codes: SA, FL1, PD, DED, ...</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Saison-Jahr</flux:label>
                <flux:input type="number" wire:model="importSeasonYear" min="2000" max="2099" />
                <flux:description>Startjahr der Saison (z. B. 2024 für 2024/25)</flux:description>
            </flux:field>

            @if ($importError)
                <div class="rounded bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                    {{ $importError }}
                </div>
            @endif
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showImportModal', false)">Abbrechen</flux:button>
            <flux:button variant="primary" wire:click="importSeason" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="importSeason">Importieren</span>
                <span wire:loading wire:target="importSeason">Lädt...</span>
            </flux:button>
        </div>
    </flux:modal>

    {{-- Saisons-Liste --}}
    @forelse ($seasons as $season)
        <div class="glass-card mb-4 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <flux:heading size="sm">{{ $season->name }}</flux:heading>
                        @if ($season->is_active)
                            <flux:badge color="green">Aktiv</flux:badge>
                        @else
                            <flux:badge color="zinc">Inaktiv</flux:badge>
                        @endif
                    </div>
                    <flux:text class="text-sm text-zinc-500">
                        Code: {{ $season->competition_code }} · Jahr: {{ $season->season_year }} ·
                        {{ $season->matches()->count() }} Spiele
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="syncSeason({{ $season->id }})" wire:loading.attr="disabled" wire:target="syncSeason({{ $season->id }})">
                        <flux:icon name="arrow-path" class="size-4" />
                        Sync
                    </flux:button>
                    <flux:button size="sm" variant="{{ $season->is_active ? 'danger' : 'primary' }}" wire:click="toggleActive({{ $season->id }})">
                        {{ $season->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                    </flux:button>
                </div>
            </div>

            {{-- Punkte-Konfiguration --}}
            <div class="mt-3 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                <flux:text class="mb-2 text-sm font-medium">Punkteregeln</flux:text>
                <div class="flex flex-wrap items-center gap-4">
                    <flux:field class="flex-row items-center gap-2">
                        <flux:label class="text-sm text-zinc-500">Exaktes Ergebnis</flux:label>
                        <flux:input
                            type="number"
                            min="0"
                            max="10"
                            value="{{ $season->points_exact_result }}"
                            wire:change="updatePoints({{ $season->id }}, $event.target.value, {{ $season->points_correct_difference }}, {{ $season->points_correct_tendency }})"
                            class="w-16 text-center"
                        />
                    </flux:field>
                    <flux:field class="flex-row items-center gap-2">
                        <flux:label class="text-sm text-zinc-500">Richtige Differenz</flux:label>
                        <flux:input
                            type="number"
                            min="0"
                            max="10"
                            value="{{ $season->points_correct_difference }}"
                            wire:change="updatePoints({{ $season->id }}, {{ $season->points_exact_result }}, $event.target.value, {{ $season->points_correct_tendency }})"
                            class="w-16 text-center"
                        />
                    </flux:field>
                    <flux:field class="flex-row items-center gap-2">
                        <flux:label class="text-sm text-zinc-500">Richtige Tendenz</flux:label>
                        <flux:input
                            type="number"
                            min="0"
                            max="10"
                            value="{{ $season->points_correct_tendency }}"
                            wire:change="updatePoints({{ $season->id }}, {{ $season->points_exact_result }}, {{ $season->points_correct_difference }}, $event.target.value)"
                            class="w-16 text-center"
                        />
                    </flux:field>
                </div>
            </div>
        </div>
    @empty
        <div class="glass-card p-8 text-center">
            <flux:icon name="calendar" class="mx-auto mb-3 size-12 text-zinc-300" />
            <flux:heading>Keine Saisons vorhanden</flux:heading>
            <flux:text class="mb-4 text-zinc-500">Importiere eine Saison, um zu beginnen.</flux:text>
        </div>
    @endforelse
</x-intranet-app-tippspiel::tippspiel-layout>
