<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use Hwkdo\IntranetAppTippspiel\Database\Factories\SeasonFactory;
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

        if ($active) {
            return $active->matchday;
        }

        $next = $this->matches()
            ->whereIn('status', [MatchStatus::Scheduled->value, MatchStatus::Timed->value])
            ->orderBy('kickoff_at')
            ->first();

        return $next?->matchday;
    }

    public function nextUntippedMatch(int $userId): ?TippspielMatch
    {
        return $this->matches()
            ->whereIn('status', [MatchStatus::Scheduled->value, MatchStatus::Timed->value])
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
