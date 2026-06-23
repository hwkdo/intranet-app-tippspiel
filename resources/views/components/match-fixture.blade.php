@props([
    'match',
    'size' => 'sm',
])

<div {{ $attributes->class('flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1') }}>
    <x-intranet-app-tippspiel::team
        :name="$match->home_team_name"
        :crest="$match->home_team_crest_url"
        side="away"
        :size="$size"
    />
    <span class="shrink-0 text-zinc-400">–</span>
    <x-intranet-app-tippspiel::team
        :name="$match->away_team_name"
        :crest="$match->away_team_crest_url"
        side="away"
        :size="$size"
    />
</div>
