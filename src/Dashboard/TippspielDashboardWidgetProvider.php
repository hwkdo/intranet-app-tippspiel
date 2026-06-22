<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Dashboard;

use Hwkdo\IntranetAppBase\Data\DashboardWidgetDefinition;
use Hwkdo\IntranetAppBase\Interfaces\DashboardWidgetProviderInterface;

class TippspielDashboardWidgetProvider implements DashboardWidgetProviderInterface
{
    public static function widgets(): array
    {
        return [
            new DashboardWidgetDefinition(
                key: 'tippspiel-rangliste',
                title: 'Tippspiel Rangliste',
                description: 'Aktuelle Rangliste der aktiven Tippspiel-Saisons',
                component: 'intranet-app-tippspiel::apps.tippspiel.widgets.rangliste',
                defaultW: 6,
                defaultH: 5,
                minW: 4,
                minH: 4,
                defaultEnabled: true,
            ),
            new DashboardWidgetDefinition(
                key: 'tippspiel-naechste-spiele',
                title: 'Tippspiel: Nächste Spiele',
                description: 'Anstehende Spiele der aktiven Tippspiel-Saisons',
                component: 'intranet-app-tippspiel::apps.tippspiel.widgets.naechste-spiele',
                defaultW: 6,
                defaultH: 4,
                minW: 4,
                minH: 3,
                defaultEnabled: true,
            ),
        ];
    }
}
