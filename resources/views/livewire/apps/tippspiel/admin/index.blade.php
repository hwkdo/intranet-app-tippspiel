
<div>
<x-intranet-app-tippspiel::tippspiel-layout
    heading="Tippspiel Admin"
    subheading="Verwaltung und Konfiguration"
>
    <flux:tab.group>
        <flux:tabs wire:model.live="activeTab">
            <flux:tab name="uebersicht" icon="home">Übersicht</flux:tab>
            <flux:tab name="ki" icon="sparkles">KI</flux:tab>
            <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
            <flux:tab name="hintergrund" icon="clock">Hintergrund</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="uebersicht">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('apps.tippspiel.admin.saisons') }}" wire:navigate>
                    <div class="glass-card cursor-pointer p-5 transition-all hover:ring-1 hover:ring-blue-500">
                        <div class="flex items-center gap-3">
                            <flux:icon name="calendar" class="size-8 text-blue-500" />
                            <div>
                                <flux:heading size="sm">Saisons</flux:heading>
                                <flux:text class="text-sm text-zinc-500">{{ $stats['active_seasons'] }} aktiv · {{ $stats['seasons'] }} gesamt</flux:text>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="ki">
            <div style="min-height: 400px;">
                @livewire(\Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin\KiEinstellungen::class)
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="einstellungen">
            <div style="min-height: 400px;">
                @livewire(\Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin\Einstellungen::class)
            </div>
        </flux:tab.panel>
    <flux:tab.panel name="hintergrund">
        @if ($activeTab === 'hintergrund')
            <div class="min-h-[400px]">
                @livewire('intranet-app-base::background-activities', ['appIdentifier' => 'tippspiel'], key('tippspiel-admin-hintergrund'))
            </div>
        @endif
    </flux:tab.panel>
    </flux:tab.group>
</x-intranet-app-tippspiel::tippspiel-layout>
</div>
