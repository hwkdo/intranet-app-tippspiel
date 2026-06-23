@props([
    'name',
    'crest' => null,
    'side' => 'away',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'sm' => ['crest' => 'size-4', 'text' => 'text-sm', 'icon' => 'size-2.5'],
        default => ['crest' => 'size-6', 'text' => 'text-sm', 'icon' => 'size-3.5'],
    };
@endphp

<div {{ $attributes->class('flex min-w-0 max-w-full items-center gap-1.5') }}>
    @if ($side === 'away')
        @if ($crest)
            <img
                src="{{ $crest }}"
                alt=""
                class="{{ $sizeClasses['crest'] }} shrink-0 object-contain"
                loading="lazy"
            />
        @else
            <span
                class="{{ $sizeClasses['crest'] }} flex shrink-0 items-center justify-center rounded-full bg-zinc-200/60 dark:bg-white/10"
                aria-hidden="true"
            >
                <flux:icon name="flag" class="{{ $sizeClasses['icon'] }} text-zinc-400" />
            </span>
        @endif
    @endif

    <span @class([
        'min-w-0 truncate font-medium',
        $sizeClasses['text'],
        'text-right' => $side === 'home',
    ])>{{ $name }}</span>

    @if ($side === 'home')
        @if ($crest)
            <img
                src="{{ $crest }}"
                alt=""
                class="{{ $sizeClasses['crest'] }} shrink-0 object-contain"
                loading="lazy"
            />
        @else
            <span
                class="{{ $sizeClasses['crest'] }} flex shrink-0 items-center justify-center rounded-full bg-zinc-200/60 dark:bg-white/10"
                aria-hidden="true"
            >
                <flux:icon name="flag" class="{{ $sizeClasses['icon'] }} text-zinc-400" />
            </span>
        @endif
    @endif
</div>
