<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run fixed asset depreciation on the 1st of every month at midnight
Schedule::command('accounting:run-depreciation')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/depreciation.log'));

// Auto-clock-out any shifts still open at 11:59 PM daily
Schedule::command('shifts:auto-clock-out')
    ->dailyAt('23:59')
    ->withoutOverlapping();

