@props([
    'leaderboard' => [],
    'currentUserGvpId' => null,
    'showTips' => true,
    'tipsColumnLabel' => 'Tipps',
    'emptyMessage' => 'Noch keine Teams mit Teilnehmern vorhanden.',
])

<flux:table>
    <flux:table.columns>
        <flux:table.column class="w-12" align="center">#</flux:table.column>
        <flux:table.column>Team (GVP)</flux:table.column>
        <flux:table.column align="end" class="w-20">Spieler</flux:table.column>
        @if ($showTips)
            <flux:table.column align="end" class="w-20">{{ $tipsColumnLabel }}</flux:table.column>
        @endif
        <flux:table.column align="end" class="w-24">Summe</flux:table.column>
        <flux:table.column align="end" class="w-28">Team-Punkte</flux:table.column>
    </flux:table.columns>
    <flux:table.rows>
        @forelse ($leaderboard as $entry)
            <flux:table.row class="{{ $entry['gvp_id'] == $currentUserGvpId ? 'bg-blue-50 dark:bg-blue-950' : '' }}">
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
                        {{ $entry['team_name'] }}
                        @if ($entry['gvp_id'] == $currentUserGvpId)
                            <flux:badge size="sm" color="blue">Mein Team</flux:badge>
                        @endif
                    </div>
                </flux:table.cell>
                <flux:table.cell align="end">{{ $entry['player_count'] }}</flux:table.cell>
                @if ($showTips)
                    <flux:table.cell align="end">
                        @if (isset($entry['evaluated_count']))
                            {{ $entry['evaluated_count'] }}/{{ $entry['tips_count'] }}
                        @else
                            {{ $entry['tips_count'] }}
                        @endif
                    </flux:table.cell>
                @endif
                <flux:table.cell align="end">{{ $entry['total_points'] }}</flux:table.cell>
                <flux:table.cell align="end" variant="strong">
                    {{ number_format($entry['team_points'], 2, ',', '.') }}
                </flux:table.cell>
            </flux:table.row>
        @empty
            <flux:table.row>
                <flux:table.cell colspan="{{ $showTips ? 6 : 5 }}" class="py-8 text-center text-zinc-500">
                    {{ $emptyMessage }}
                </flux:table.cell>
            </flux:table.row>
        @endforelse
    </flux:table.rows>
</flux:table>
