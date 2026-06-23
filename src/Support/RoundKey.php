<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Hwkdo\IntranetAppTippspiel\Models\Season;
use InvalidArgumentException;

final class RoundKey
{
    public static function toSlug(string $key): string
    {
        if (preg_match('/^md:\d+$/', $key) === 1) {
            return 'md-'.substr($key, 3);
        }

        if (preg_match('/^stage:.+$/', $key) === 1) {
            return 'stage-'.substr($key, 6);
        }

        throw new InvalidArgumentException("Ungültiger Runden-Schlüssel: {$key}");
    }

    public static function fromSlug(string $slug): string
    {
        if (preg_match('/^md-(\d+)$/', $slug, $matches) === 1) {
            return 'md:'.$matches[1];
        }

        if (preg_match('/^stage-(.+)$/', $slug, $matches) === 1) {
            return 'stage:'.$matches[1];
        }

        throw new InvalidArgumentException("Ungültiger Runden-Slug: {$slug}");
    }

    public static function route(Season $season, string $roundKey): string
    {
        return route('apps.tippspiel.auswertung', [
            'season' => $season,
            'roundSlug' => self::toSlug($roundKey),
        ]);
    }
}
