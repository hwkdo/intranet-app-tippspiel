<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Tasks;

use Hwkdo\IntranetAppTippspiel\IntranetAppTippspiel;
use Hwkdo\IntranetAppTippspiel\Models\Participant;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppBase\Data\TaskItem;
use Hwkdo\IntranetAppBase\Interfaces\TaskProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class NaechstenSpieltippenTaskProvider implements TaskProviderInterface
{
    /**
     * Gibt für jede aktive Saison, in der der User angemeldet ist,
     * einen TaskItem zurück, wenn das nächste Spiel noch ungetippt ist.
     *
     * @return Collection<int, TaskItem>
     */
    public function getTasksForUser(Authenticatable $user): Collection
    {
        $userId = $user->getAuthIdentifier();
        $tasks = collect();

        foreach (Season::active() as $season) {
            $participant = Participant::where('season_id', $season->id)
                ->where('user_id', $userId)
                ->first();

            if ($participant === null) {
                continue;
            }

            $nextUntipped = $season->nextUntippedMatch($userId);

            if ($nextUntipped === null) {
                continue;
            }

            $kickoff = $nextUntipped->kickoff_at?->format('d.m. H:i');
            $description = "{$nextUntipped->home_team_name} – {$nextUntipped->away_team_name}"
                .($kickoff ? " · Anpfiff: {$kickoff} Uhr" : '');

            $tasks->push(new TaskItem(
                title: 'Tipp abgeben: '.$season->name,
                url: route('apps.tippspiel.tippen', $season),
                appIdentifier: IntranetAppTippspiel::identifier(),
                appName: IntranetAppTippspiel::app_name(),
                appIcon: IntranetAppTippspiel::app_icon(),
                description: $description,
                badge: 'Spieltag '.($nextUntipped->matchday ?? ''),
                priority: 7,
            ));
        }

        return $tasks;
    }

    public function getLabel(): string
    {
        return 'Offene Tipps';
    }
}
