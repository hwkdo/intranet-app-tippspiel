<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use App\Models\User;
use Hwkdo\IntranetAppTippspiel\Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    protected $table = 'intranet_app_tippspiel_participants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<Tip, $this> */
    public function tips(): HasMany
    {
        return $this->hasMany(Tip::class, 'participant_id');
    }

    public function recalculateTotalPoints(): void
    {
        $this->total_points = $this->tips()->whereNotNull('points_earned')->sum('points_earned');
        $this->save();
    }
}
