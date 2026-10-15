<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Background;

use Hwkdo\IntranetAppBase\Data\BackgroundActivityDefinition;
use Hwkdo\IntranetAppBase\Data\BackgroundActivityFrequency;
use Hwkdo\IntranetAppBase\Enums\BackgroundActivityCadence;
use Hwkdo\IntranetAppTippspiel\IntranetAppTippspiel;

final class TippspielBackgroundActivities
{
    /**
     * @return list<BackgroundActivityDefinition>
     */
    public static function definitions(): array
    {
        $id = IntranetAppTippspiel::identifier();
        $name = IntranetAppTippspiel::app_name();
        $all = BackgroundActivityCadence::cases();

        return [
            BackgroundActivityDefinition::scheduled(
                key: 'tippspiel.sync-matches',
                label: 'Spiele synchronisieren',
                appIdentifier: $id,
                appName: $name,
                command: 'tippspiel:sync-matches',
                frequency: BackgroundActivityFrequency::everyHours(1),
                description: 'Synchronisiert Spielpläne und Ergebnisse.',
                allowedCadences: $all,
                minEvery: 1,
                maxEvery: 24,
                withoutOverlappingMinutes: 1440,
                runInBackground: true,
                appendOutputTo: storage_path('logs/tippspiel-sync.log'),
                sort: 10,
            ),
            BackgroundActivityDefinition::scheduled(
                key: 'tippspiel.evaluate-tips',
                label: 'Tipps auswerten',
                appIdentifier: $id,
                appName: $name,
                command: 'tippspiel:evaluate-tips',
                frequency: BackgroundActivityFrequency::everyMinutes(15),
                description: 'Wertet Tipps aus und vergibt Punkte.',
                allowedCadences: $all,
                minEvery: 5,
                maxEvery: 60,
                withoutOverlappingMinutes: 1440,
                runInBackground: true,
                appendOutputTo: storage_path('logs/tippspiel-evaluate.log'),
                sort: 20,
            ),
        ];
    }
}
