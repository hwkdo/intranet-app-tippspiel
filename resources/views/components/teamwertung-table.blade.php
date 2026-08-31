@props([
    'leaderboard' => [],
    'currentUserGvpId' => null,
    'showTips' => true,
    'tipsColumnLabel' => 'Tipps',
    'teamColumnLabel' => 'Team (GVP)',
    'emptyMessage' => 'Noch keine Teams mit Teilnehmern vorhanden.',
    'mode' => 'group',
])

@php
    $colspan = $showTips ? 6 : 5;
@endphp

<div
    wire:key="tippspiel-teamwertung-{{ $mode }}"
    x-data
    x-init="
        if (! window.__tippspielTeamExpandReady) {
            Alpine.store('tippspielTeamExpand', {
                openTeams: {},
                openGroups: {},
                toggleTeam(key) {
                    this.openTeams = { ...this.openTeams, [key]: ! this.openTeams[key] };
                },
                toggleGroup(key) {
                    this.openGroups = { ...this.openGroups, [key]: ! this.openGroups[key] };
                },
            });
            window.__tippspielTeamExpandReady = true;
        }
    "
>
    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-12" align="center">#</flux:table.column>
            <flux:table.column>{{ $teamColumnLabel }}</flux:table.column>
            <flux:table.column align="end" class="w-20">Spieler</flux:table.column>
            @if ($showTips)
                <flux:table.column align="end" class="w-20">{{ $tipsColumnLabel }}</flux:table.column>
            @endif
            <flux:table.column align="end" class="w-24">Summe</flux:table.column>
            <flux:table.column align="end" class="w-28">Team-Punkte</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($leaderboard as $entry)
                @php
                    $teamKey = $mode.'-t'.$entry['gvp_id'];
                    $isMine = $entry['gvp_id'] == $currentUserGvpId;
                @endphp
                <flux:table.row class="{{ $isMine ? 'bg-blue-50/70 dark:bg-blue-500/10' : '' }}">
                    <flux:table.cell align="center">
                        @if ($entry['rank'] === 1)
                            <flux:icon name="trophy" class="size-4 text-yellow-500" />
                        @elseif ($entry['rank'] === 2)
                            <flux:icon name="trophy" class="size-4 text-zinc-400" />
                        @elseif ($entry['rank'] === 3)
                            <flux:icon name="trophy" class="size-4 text-amber-700 dark:text-amber-500" />
                        @else
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $entry['rank'] }}.</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex size-7 shrink-0 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-900/5 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/10 dark:hover:text-zinc-200"
                                x-on:click="$store.tippspielTeamExpand.toggleTeam('{{ $teamKey }}')"
                                x-bind:aria-expanded="!!$store.tippspielTeamExpand.openTeams['{{ $teamKey }}']"
                                aria-label="Details umschalten"
                            >
                                <span x-show="!$store.tippspielTeamExpand.openTeams['{{ $teamKey }}']" class="inline-flex">
                                    <flux:icon name="chevron-right" class="size-4" />
                                </span>
                                <span x-show="!!$store.tippspielTeamExpand.openTeams['{{ $teamKey }}']" x-cloak class="inline-flex">
                                    <flux:icon name="chevron-down" class="size-4" />
                                </span>
                            </button>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry['team_name'] }}</span>
                            @if ($isMine)
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

                <tr x-show="!!$store.tippspielTeamExpand.openTeams['{{ $teamKey }}']" x-cloak>
                    <td colspan="{{ $colspan }}" class="!p-0">
                        <div
                            class="ml-4 border-l-2 border-blue-500/30 py-2 pl-4 dark:border-blue-400/25 sm:ml-6 sm:pl-5"
                            x-show="!!$store.tippspielTeamExpand.openTeams['{{ $teamKey }}']"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                        >
                            @if ($mode === 'department')
                                <div class="space-y-1">
                                    <p class="mb-2 text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                                        Gruppen / Fachbereiche
                                    </p>
                                    @foreach (($entry['groups'] ?? []) as $group)
                                        @php
                                            $groupKey = $teamKey.'-g'.$group['gvp_id'].($group['is_direct_assignment'] ?? false ? '-direct' : '');
                                            $isDirect = (bool) ($group['is_direct_assignment'] ?? false);
                                        @endphp
                                        <div>
                                            <button
                                                type="button"
                                                class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm transition hover:bg-zinc-900/5 dark:hover:bg-white/5"
                                                x-on:click="$store.tippspielTeamExpand.toggleGroup('{{ $groupKey }}')"
                                                x-bind:aria-expanded="!!$store.tippspielTeamExpand.openGroups['{{ $groupKey }}']"
                                            >
                                                <span class="inline-flex size-4 shrink-0 items-center justify-center text-zinc-400 dark:text-zinc-500">
                                                    <span x-show="!$store.tippspielTeamExpand.openGroups['{{ $groupKey }}']" class="inline-flex">
                                                        <flux:icon name="chevron-right" class="size-4" />
                                                    </span>
                                                    <span x-show="!!$store.tippspielTeamExpand.openGroups['{{ $groupKey }}']" x-cloak class="inline-flex">
                                                        <flux:icon name="chevron-down" class="size-4" />
                                                    </span>
                                                </span>
                                                <span class="min-w-0 flex-1 truncate font-medium text-zinc-800 dark:text-zinc-100">
                                                    {{ $group['team_name'] }}
                                                </span>
                                                @if ($isDirect)
                                                    <flux:badge size="sm" color="zinc">Direkt</flux:badge>
                                                @endif
                                                <span class="hidden text-zinc-500 sm:inline dark:text-zinc-400">
                                                    {{ $group['player_count'] }} Spieler
                                                </span>
                                                @if ($showTips)
                                                    <span class="hidden w-14 text-end text-zinc-500 tabular-nums md:inline dark:text-zinc-400">
                                                        @if (isset($group['evaluated_count']))
                                                            {{ $group['evaluated_count'] }}/{{ $group['tips_count'] }}
                                                        @else
                                                            {{ $group['tips_count'] }}
                                                        @endif
                                                    </span>
                                                @endif
                                                <span class="w-14 text-end text-zinc-500 tabular-nums dark:text-zinc-400">
                                                    {{ $group['total_points'] }}
                                                </span>
                                                <span class="w-16 text-end font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                                                    {{ number_format($group['team_points'], 2, ',', '.') }}
                                                </span>
                                            </button>
                                            <div
                                                class="ml-3 border-l border-zinc-300/70 py-1 pl-3 dark:border-white/10"
                                                x-show="!!$store.tippspielTeamExpand.openGroups['{{ $groupKey }}']"
                                                x-cloak
                                                x-transition
                                            >
                                                <x-intranet-app-tippspiel::teamwertung-members
                                                    :members="$group['members'] ?? []"
                                                    :show-tips="$showTips"
                                                    :tips-column-label="$tipsColumnLabel"
                                                />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-intranet-app-tippspiel::teamwertung-members
                                    :members="$entry['members'] ?? []"
                                    :show-tips="$showTips"
                                    :tips-column-label="$tipsColumnLabel"
                                />
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ $colspan }}" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        {{ $emptyMessage }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
