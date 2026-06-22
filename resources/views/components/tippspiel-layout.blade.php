@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => [],
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.tippspiel.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht'],
        ['type' => 'separator', 'label' => 'Saisons'],
    ];

    foreach (\Hwkdo\IntranetAppTippspiel\Models\Season::active() as $season) {
        $defaultNavItems[] = [
            'label' => $season->name,
            'href' => route('apps.tippspiel.tippen', $season),
            'icon' => 'trophy',
            'description' => 'Spiele tippen',
        ];
    }

    $defaultNavItems[] = ['type' => 'separator', 'label' => 'Admin', 'permission' => 'manage-app-tippspiel'];
    $defaultNavItems[] = ['label' => 'Saisons verwalten', 'href' => route('apps.tippspiel.admin.saisons'), 'icon' => 'calendar', 'description' => 'Saisons anlegen und aktivieren', 'permission' => 'manage-app-tippspiel'];
    $defaultNavItems[] = ['label' => 'Einstellungen', 'href' => route('apps.tippspiel.admin.einstellungen'), 'icon' => 'cog-6-tooth', 'description' => 'Punkte und KI-Provider konfigurieren', 'permission' => 'manage-app-tippspiel'];

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
