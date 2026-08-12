@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => [],
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.tippspiel.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht'],
        [
            'label' => 'Ewige Rangliste',
            'href' => route('apps.tippspiel.ewige-rangliste'),
            'icon' => 'trophy',
            'description' => 'Saisonübergreifende Gesamtwertung',
        ],
        ['type' => 'separator', 'label' => 'Saisons'],
    ];

    foreach (\Hwkdo\IntranetAppTippspiel\Models\Season::active() as $season) {
        $defaultNavItems[] = [
            'label' => $season->name.' — Tippen',
            'href' => route('apps.tippspiel.tippen', $season),
            'icon' => 'trophy',
            'description' => 'Spiele tippen',
        ];
        $defaultNavItems[] = [
            'label' => $season->name.' — Auswertungen',
            'href' => route('apps.tippspiel.auswertungen', $season),
            'icon' => 'chart-bar',
            'description' => 'Tipps und Punkte pro Runde',
        ];
        $defaultNavItems[] = [
            'label' => $season->name.' — Rangliste',
            'href' => route('apps.tippspiel.rangliste', $season),
            'icon' => 'list-bullet',
            'description' => 'Gesamtrangliste',
        ];
    }

    if (\Hwkdo\IntranetAppTippspiel\Models\Season::where('is_active', false)->exists()) {
        $defaultNavItems[] = ['type' => 'separator', 'label' => 'Archiv'];

        foreach (\Hwkdo\IntranetAppTippspiel\Models\Season::archived() as $archivedSeason) {
            $defaultNavItems[] = [
                'label' => $archivedSeason->name,
                'href' => route('apps.tippspiel.archiv', $archivedSeason),
                'icon' => 'archive-box',
                'description' => 'Rangliste und Auswertungen',
            ];
        }
    }

    $defaultNavItems[] = ['type' => 'separator', 'label' => 'Admin', 'permission' => 'manage-app-tippspiel'];
    $defaultNavItems[] = ['label' => 'Admin', 'href' => route('apps.tippspiel.admin.index'), 'icon' => 'wrench-screwdriver', 'description' => 'Saisons, Einstellungen und KI', 'permission' => 'manage-app-tippspiel'];

    $navItems = !empty($navItems) ? $navItems : $defaultNavItems;
@endphp

<x-intranet-app-base::app-layout
    :heading="$heading"
    :subheading="$subheading"
    :nav-items="$navItems"
    app-identifier="tippspiel"
>
    {{ $slot }}
</x-intranet-app-base::app-layout>
