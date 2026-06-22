<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use Hwkdo\IntranetAppTippspiel\Database\Factories\TippspielMatchFactory;
use Hwkdo\IntranetAppTippspiel\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TippspielMatch extends Model
{
    /** @use HasFactory<TippspielMatchFactory> */
    use HasFactory;

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

    public function getScoreDisplayAttribute(): string
    {
        if ($this->home_score === null || $this->away_score === null) {
            return '-:-';
        }

        return $this->home_score.':'.$this->away_score;
    }
}
