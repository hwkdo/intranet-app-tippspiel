<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use Hwkdo\IntranetAppTippspiel\Data\SeasonRound;
use Hwkdo\IntranetAppTippspiel\Database\Factories\SeasonFactory;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStage;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    protected $table = 'intranet_app_tippspiel_seasons';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    /** @return HasMany<TippspielMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(TippspielMatch::class, 'season_id');
    }

    /** @return HasMany<Participant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'season_id');
    }

    public function currentMatchday(): ?int
    {
        $roundKey = $this->currentRoundKey();

        if ($roundKey === null || ! str_starts_with($roundKey, 'md:')) {
            return null;
        }

        return (int) substr($roundKey, 3);
    }

    public function currentRoundKey(): ?string
    {
        $active = $this->matches()
            ->where(function ($q) {
                $q->whereIn('status', [
                    MatchStatus::InPlay->value,
                    MatchStatus::Paused->value,
                    MatchStatus::ExtraTime->value,
                    MatchStatus::PenaltyShootout->value,
                ]);
            })
            ->orderBy('kickoff_at')
            ->first();

        if ($active !== null) {
            return $active->roundKey();
        }

        $next = $this->matches()
            ->stillTippable()
            ->orderBy('kickoff_at')
            ->first();

        return $next?->roundKey();
    }

    /**
     * @return \Illuminate\Support\Collection<int, SeasonRound>
     */
    public function availableRounds(bool $tippableOnly = false): \Illuminate\Support\Collection
    {
        $query = $this->matches();

        if ($tippableOnly) {
            $query->withKnownTeams();
        }

        $rounds = collect();

        $matchdays = (clone $query)
            ->whereNotNull('matchday')
            ->distinct()
            ->orderBy('matchday')
            ->pluck('matchday');

        foreach ($matchdays as $matchday) {
            $rounds->push(new SeasonRound(
                key: 'md:'.$matchday,
                label: 'Spieltag '.$matchday,
                sortOrder: (int) $matchday,
            ));
        }

        $stages = (clone $query)
            ->whereNull('matchday')
            ->distinct()
            ->pluck('stage');

        foreach ($stages as $stage) {
            if ($stage === null || $stage === '') {
                continue;
            }

            $enum = MatchStage::tryFrom($stage);

            $rounds->push(new SeasonRound(
                key: 'stage:'.$stage,
                label: MatchStage::labelFor($stage),
                sortOrder: 1000 + ($enum?->sortOrder() ?? 999),
            ));
        }

        return $rounds->sortBy('sortOrder')->values();
    }

    public function defaultRoundKey(bool $tippableOnly = false): ?string
    {
        return $this->currentRoundKey()
            ?? $this->availableRounds($tippableOnly)->last()?->key;
    }

    public function nextUntippedMatch(int $userId): ?TippspielMatch
    {
        return $this->matches()
            ->stillTippable()
            ->whereDoesntHave('tips', function ($q) use ($userId) {
                $q->whereHas('participant', fn ($p) => $p->where('user_id', $userId)->where('season_id', $this->id));
            })
            ->orderBy('kickoff_at')
            ->first();
    }

    /** @return \Illuminate\Support\Collection<int, Season> */
    public static function active(): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)->orderBy('season_year', 'desc')->get();
    }
}
