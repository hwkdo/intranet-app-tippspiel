<flux:card class="glass-card">
    <flux:heading size="lg" class="mb-2">Einstellungen</flux:heading>
    <flux:text class="mb-6 text-sm text-zinc-500">
        Fachliche App-Einstellungen. KI-Konfiguration liegt im Tab „KI“.
    </flux:text>

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

    <div class="mt-6 flex justify-end">
        <flux:button variant="primary" wire:click="save">Einstellungen speichern</flux:button>
    </div>
</flux:card>
