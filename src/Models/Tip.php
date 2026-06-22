<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use Hwkdo\IntranetAppTippspiel\Database\Factories\TipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tip extends Model
{
    /** @use HasFactory<TipFactory> */
    use HasFactory;

    protected $table = 'intranet_app_tippspiel_tips';

    protected $guarded = [];

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    /** @return BelongsTo<TippspielMatch, $this> */
    public function match(): BelongsTo
    {
        return $this->belongsTo(TippspielMatch::class, 'match_id');
    }

    public function getScoreDisplayAttribute(): string
    {
        return $this->home_score_tip.':'.$this->away_score_tip;
    }
}
