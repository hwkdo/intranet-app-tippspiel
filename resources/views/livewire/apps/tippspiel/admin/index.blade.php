<x-intranet-app-tippspiel::tippspiel-layout
    heading="Tippspiel Admin"
    subheading="Verwaltung und Konfiguration"
>
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

        <a href="{{ route('apps.tippspiel.admin.einstellungen') }}" wire:navigate>
            <div class="glass-card cursor-pointer p-5 transition-all hover:ring-1 hover:ring-blue-500">
                <div class="flex items-center gap-3">
                    <flux:icon name="cog-6-tooth" class="size-8 text-zinc-500" />
                    <div>
                        <flux:heading size="sm">Einstellungen</flux:heading>
                        <flux:text class="text-sm text-zinc-500">Punkte & KI-Provider</flux:text>
                    </div>
                </div>
            </div>
        </a>
    </div>
</x-intranet-app-tippspiel::tippspiel-layout>
