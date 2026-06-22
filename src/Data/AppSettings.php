<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Data;

use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;

class AppSettings extends BaseAppSettings
{
    public function __construct(
        #[Description('KI-News-Provider (langdock oder openwebui)')]
        public string $aiNewsProvider = 'langdock',

        #[Description('Modell für die KI-News-Generierung')]
        public string $aiNewsModel = '',

        #[Description('KI-News-Generierung aktivieren')]
        public bool $aiNewsEnabled = false,

        #[Description('Kategorie-ID für automatisch erstellte News (0 = nicht gesetzt)')]
        public int $aiNewsKategorieId = 0,

        #[Description('Publisher-User-ID für automatisch erstellte News (0 = nicht gesetzt)')]
        public int $aiNewsPublisherId = 0,

        #[Description('Standard-Punkte für exaktes Ergebnis')]
        public int $defaultPointsExactResult = 3,

        #[Description('Standard-Punkte für richtige Tordifferenz')]
        public int $defaultPointsCorrectDifference = 2,

        #[Description('Standard-Punkte für richtige Tendenz')]
        public int $defaultPointsCorrectTendency = 1,
    ) {}
}
