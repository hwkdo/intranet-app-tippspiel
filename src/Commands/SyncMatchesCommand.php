<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Commands;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Services\FootballDataSyncService;
use Illuminate\Console\Command;

class SyncMatchesCommand extends Command
{
    protected $signature = 'tippspiel:sync-matches
                            {season? : ID der Saison (ohne Angabe: alle aktiven Saisons)}
                            {--full : Vollständigen Sync aller Spieltage erzwingen}';

    protected $description = 'Synchronisiert Spielpläne und Ergebnisse von football-data.org';

    public function handle(FootballDataSyncService $syncService): int
    {
        $seasonId = $this->argument('season');
        $full = $this->option('full');

        if ($seasonId !== null) {
            $seasons = Season::where('id', $seasonId)->get();
        } else {
            $seasons = Season::active();
        }

        if ($seasons->isEmpty()) {
            $this->info('Keine aktiven Saisons gefunden.');

            return self::SUCCESS;
        }

        foreach ($seasons as $season) {
            $this->info("Synchronisiere Saison: {$season->name}");

            try {
                if ($full) {
                    $count = $syncService->syncAllMatches($season);
                    $this->info("  → {$count} Spiele vollständig synchronisiert.");
                } else {
                    $count = $syncService->syncCurrentMatchday($season);
                    $this->info("  → {$count} Spiele des aktuellen Spieltages synchronisiert.");
                }
            } catch (\Throwable $e) {
                $this->error("  Fehler beim Sync: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
