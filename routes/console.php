<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('audit:prune')->daily();

// Backup database harian pukul 02:30 WIB; retensi default 14 hari (ubah via --keep).
Schedule::command('backup:database')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('posts:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('reports:email-monthly')
    ->monthlyOn(1, '07:00')
    ->withoutOverlapping()
    ->onOneServer();
