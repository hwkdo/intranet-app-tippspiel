<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Enums;

enum MatchStage: string
{
    case GroupStage = 'GROUP_STAGE';
    case Last64 = 'LAST_64';
    case Last32 = 'LAST_32';
    case Last16 = 'LAST_16';
    case QuarterFinals = 'QUARTER_FINALS';
    case SemiFinals = 'SEMI_FINALS';
    case ThirdPlace = 'THIRD_PLACE';
    case Final = 'FINAL';
    case Playoffs = 'PLAYOFFS';
    case PlayoffRound1 = 'PLAYOFF_ROUND_1';
    case PlayoffRound2 = 'PLAYOFF_ROUND_2';
    case Round1 = 'ROUND_1';
    case Round2 = 'ROUND_2';
    case Round3 = 'ROUND_3';
    case Round4 = 'ROUND_4';
    case RegularSeason = 'REGULAR_SEASON';

    public function label(): string
    {
        return match ($this) {
            self::GroupStage => 'Gruppenphase',
            self::Last64 => 'Runde der letzten 64',
            self::Last32 => 'Sechzehntelfinale',
            self::Last16 => 'Achtelfinale',
            self::QuarterFinals => 'Viertelfinale',
            self::SemiFinals => 'Halbfinale',
            self::ThirdPlace => 'Spiel um Platz 3',
            self::Final => 'Finale',
            self::Playoffs => 'Playoffs',
            self::PlayoffRound1 => 'Playoff Runde 1',
            self::PlayoffRound2 => 'Playoff Runde 2',
            self::Round1 => 'Runde 1',
            self::Round2 => 'Runde 2',
            self::Round3 => 'Runde 3',
            self::Round4 => 'Runde 4',
            self::RegularSeason => 'Hauptrunde',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::GroupStage => 10,
            self::RegularSeason => 10,
            self::Playoffs => 20,
            self::PlayoffRound1 => 21,
            self::PlayoffRound2 => 22,
            self::Round1 => 30,
            self::Round2 => 31,
            self::Round3 => 32,
            self::Round4 => 33,
            self::Last64 => 40,
            self::Last32 => 50,
            self::Last16 => 60,
            self::QuarterFinals => 70,
            self::SemiFinals => 80,
            self::ThirdPlace => 90,
            self::Final => 100,
        };
    }

    public static function labelFor(?string $stage): string
    {
        if ($stage === null || $stage === '') {
            return 'Unbekannte Runde';
        }

        return self::tryFrom($stage)?->label()
            ?? ucfirst(strtolower(str_replace('_', ' ', $stage)));
    }

}
