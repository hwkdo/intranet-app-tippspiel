<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Data;

readonly class SeasonRound
{
    public function __construct(
        public string $key,
        public string $label,
        public int $sortOrder,
    ) {}
}
