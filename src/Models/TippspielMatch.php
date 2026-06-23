<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use Hwkdo\IntranetAppTippspiel\Database\Factories\TippspielMatchFactory;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStage;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TippspielMatch extends Model
{
    /** @use HasFactory<TippspielMatchFactory> */
    use HasFactory;

    /** @var list<string> */
    public const PLACEHOLDER_TEAM_NAMES = ['Unbekannt', 'TBD', 'To be determined'];

    protected $table = 'intranet_app_tippspiel_matches';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kickoff_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'status' => MatchStatus::class,
        ];
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    /** @return HasMany<Tip, $this> */
    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class, 'match_id');
    }

    public function isFinished(): bool
    {
        return $this->status?->isFinished() ?? false;
    }

    public function isTippable(): bool
    {
        return $this->status?->isTippable() ?? false;
    }

    public function kickoffHasPassed(): bool
    {
        return $this->kickoff_at !== null && $this->kickoff_at->isPast();
    }

    public function canStillBeTipped(): bool
    {
        return $this->hasKnownTeams() && $this->isTippable() && ! $this->kickoffHasPassed();
    }

    public function hasKnownTeams(): bool
    {
        return ! self::isPlaceholderTeamName($this->home_team_name)
            && ! self::isPlaceholderTeamName($this->away_team_name);
    }

    public static function isPlaceholderTeamName(?string $name): bool
    {
        if ($name === null || trim($name) === '') {
            return true;
        }

        $normalized = mb_strtolower(trim($name));

        foreach (self::PLACEHOLDER_TEAM_NAMES as $placeholder) {
            if ($normalized === mb_strtolower($placeholder)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $team
     */
    public static function teamDataIsKnown(?array $team): bool
    {
        if ($team === null) {
            return false;
        }

        return isset($team['id']) && $team['id'] !== null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<TippspielMatch>  $query
     */
    public function scopeWithKnownTeams($query): void
    {
        $query->whereNotIn('home_team_name', self::PLACEHOLDER_TEAM_NAMES)
            ->whereNotIn('away_team_name', self::PLACEHOLDER_TEAM_NAMES)
            ->where('home_team_name', '!=', '')
            ->where('away_team_name', '!=', '');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<TippspielMatch>  $query
     */
    public function scopeStillTippable($query): void
    {
        $query->whereIn('status', [MatchStatus::Scheduled->value, MatchStatus::Timed->value])
            ->where(function ($query) {
                $query->whereNull('kickoff_at')
                    ->orWhere('kickoff_at', '>=', now());
            })
            ->withKnownTeams();
    }

    public function roundKey(): string
    {
        if ($this->matchday !== null) {
            return 'md:'.$this->matchday;
        }

        return 'stage:'.($this->stage ?? 'UNKNOWN');
    }

    public function roundLabel(): string
    {
        if ($this->matchday !== null) {
            return 'Spieltag '.$this->matchday;
        }

        return MatchStage::labelFor($this->stage);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<TippspielMatch>  $query
     */
    public function scopeForRoundKey($query, string $roundKey): void
    {
        if (str_starts_with($roundKey, 'md:')) {
            $query->where('matchday', (int) substr($roundKey, 3));

            return;
        }

        if (str_starts_with($roundKey, 'stage:')) {
            $query->where('stage', substr($roundKey, 6));
        }
    }

    public function getScoreDisplayAttribute(): string
    {
        if ($this->home_score === null || $this->away_score === null) {
            return '-:-';
        }

        return $this->home_score.':'.$this->away_score;
    }
}
