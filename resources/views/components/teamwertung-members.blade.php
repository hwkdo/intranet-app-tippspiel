@props([
    'members' => [],
    'showTips' => true,
    'tipsColumnLabel' => 'Tipps',
])

@if ($members === [])
    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Keine Mitspieler.</flux:text>
@else
    <table class="min-w-full text-sm">
        <thead>
            <tr class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">
                <th class="px-2 py-1.5 text-left font-medium">Mitspieler</th>
                @if ($showTips)
                    <th class="w-20 px-2 py-1.5 text-end font-medium">{{ $tipsColumnLabel }}</th>
                @endif
                <th class="w-24 px-2 py-1.5 text-end font-medium">Punkte</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @foreach ($members as $member)
                <tr class="text-zinc-700 dark:text-zinc-200">
                    <td class="px-2 py-2">{{ $member['user_name'] }}</td>
                    @if ($showTips)
                        <td class="px-2 py-2 text-end tabular-nums text-zinc-500 dark:text-zinc-400">
                            @if (isset($member['evaluated_count']))
                                {{ $member['evaluated_count'] }}/{{ $member['tips_count'] }}
                            @else
                                {{ $member['tips_count'] }}
                            @endif
                        </td>
                    @endif
                    <td class="px-2 py-2 text-end font-medium tabular-nums text-zinc-900 dark:text-zinc-50">
                        {{ $member['total_points'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
