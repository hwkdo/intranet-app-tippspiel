<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:see-app-tippspiel'])->group(function () {
    Route::livewire('apps/tippspiel', 'intranet-app-tippspiel::apps.tippspiel.dashboard')->name('apps.tippspiel.index');
    Route::livewire('apps/tippspiel/tippen/{season}', 'intranet-app-tippspiel::apps.tippspiel.tippen')->name('apps.tippspiel.tippen');
    Route::livewire('apps/tippspiel/rangliste/{season}', 'intranet-app-tippspiel::apps.tippspiel.rangliste')->name('apps.tippspiel.rangliste');
    Route::livewire('apps/tippspiel/ergebnisse/{season}', 'intranet-app-tippspiel::apps.tippspiel.ergebnisse')->name('apps.tippspiel.ergebnisse');
    Route::livewire('apps/tippspiel/auswertungen/{season}', 'intranet-app-tippspiel::apps.tippspiel.auswertungen')->name('apps.tippspiel.auswertungen');
    Route::livewire('apps/tippspiel/auswertung/{season}/{roundSlug}', 'intranet-app-tippspiel::apps.tippspiel.runden-auswertung')
        ->where('roundSlug', 'md-\d+|stage-.+')
        ->name('apps.tippspiel.auswertung');

    Route::livewire('apps/tippspiel/admin', 'intranet-app-tippspiel::apps.tippspiel.admin.index')
        ->middleware('can:manage-app-tippspiel')
        ->name('apps.tippspiel.admin.index');
    Route::livewire('apps/tippspiel/admin/saisons', 'intranet-app-tippspiel::apps.tippspiel.admin.saisons')
        ->middleware('can:manage-app-tippspiel')
        ->name('apps.tippspiel.admin.saisons');
    Route::livewire('apps/tippspiel/admin/einstellungen', 'intranet-app-tippspiel::apps.tippspiel.admin.einstellungen')
        ->middleware('can:manage-app-tippspiel')
        ->name('apps.tippspiel.admin.einstellungen');
});
