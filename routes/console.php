<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('tippspiel:sync-matches')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tippspiel-sync.log'));

Schedule::command('tippspiel:evaluate-tips')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tippspiel-evaluate.log'));
