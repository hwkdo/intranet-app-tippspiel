<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Enums;

enum MatchStatus: string
{
    case Scheduled = 'SCHEDULED';
    case Timed = 'TIMED';
    case InPlay = 'IN_PLAY';
    case Paused = 'PAUSED';
    case ExtraTime = 'EXTRA_TIME';
    case PenaltyShootout = 'PENALTY_SHOOTOUT';
    case Finished = 'FINISHED';
    case Suspended = 'SUSPENDED';
    case Postponed = 'POSTPONED';
    case Cancelled = 'CANCELLED';
    case Awarded = 'AWARDED';

    public function isFinished(): bool
    {
        return $this === self::Finished || $this === self::Awarded;
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::InPlay,
            self::Paused,
            self::ExtraTime,
            self::PenaltyShootout,
        ], true);
    }

    public function isTippable(): bool
    {
        return in_array($this, [
            self::Scheduled,
            self::Timed,
        ], true);
    }
}
