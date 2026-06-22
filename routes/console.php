<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;

/** @var Schedule $schedule */
$schedule = App::make(Schedule::class);

$schedule->command('tippspiel:sync-matches')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tippspiel-sync.log'));

$schedule->command('tippspiel:evaluate-tips')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tippspiel-evaluate.log'));
