<x-intranet-app-tippspiel::tippspiel-layout
    heading="Einstellungen"
    subheading="Punkte-Regeln und KI-Provider konfigurieren"
>
    <div class="glass-card p-6">
        <div class="space-y-6">
            {{-- Standard-Punkteregeln --}}
            <div>
                <flux:heading size="sm" class="mb-3">Standard-Punkteregeln für neue Saisons</flux:heading>
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Exaktes Ergebnis</flux:label>
                        <flux:input type="number" wire:model="defaultPointsExactResult" min="0" max="10" />
                        <flux:description>z. B. Tipp: 2:1 | Ergebnis: 2:1</flux:description>
                    </flux:field>
                    <flux:field>
                        <flux:label>Richtige Tordifferenz</flux:label>
                        <flux:input type="number" wire:model="defaultPointsCorrectDifference" min="0" max="10" />
                        <flux:description>z. B. Tipp: 2:1 | Ergebnis: 3:2</flux:description>
                    </flux:field>
                    <flux:field>
                        <flux:label>Richtige Tendenz</flux:label>
                        <flux:input type="number" wire:model="defaultPointsCorrectTendency" min="0" max="10" />
                        <flux:description>z. B. Tipp: 2:1 | Ergebnis: 1:0</flux:description>
                    </flux:field>
                </div>
            </div>

            <flux:separator />

            {{-- KI-News --}}
            <div>
                <flux:heading size="sm" class="mb-3">KI-Newsartikel nach Spieltag</flux:heading>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>KI-News aktivieren</flux:label>
                        <flux:switch wire:model="aiNewsEnabled" />
                        <flux:description>Automatisch einen Newsartikel nach jedem abgeschlossenen Spieltag erstellen</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>KI-Provider</flux:label>
                        <flux:select wire:model="aiNewsProvider">
                            <flux:select.option value="langdock">Langdock</flux:select.option>
                            <flux:select.option value="openwebui">OpenWebUI</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Modell</flux:label>
                        <flux:input wire:model="aiNewsModel" placeholder="z. B. gpt-4o oder gpt-oss:20b" />
                        <flux:description>Leer lassen für Provider-Standard</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Kategorie-ID für News</flux:label>
                        <flux:input type="number" wire:model="aiNewsKategorieId" min="0" />
                        <flux:description>ID aus der Tabelle „kategories" (0 = nicht gesetzt)</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Publisher-User-ID</flux:label>
                        <flux:input type="number" wire:model="aiNewsPublisherId" min="0" />
                        <flux:description>User-ID für den Autor der generierten News (0 = nicht gesetzt)</flux:description>
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" wire:click="save">Einstellungen speichern</flux:button>
        </div>
    </div>
</x-intranet-app-tippspiel::tippspiel-layout>
