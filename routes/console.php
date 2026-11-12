<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('forista:dispatch-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('forista:generate-sessions')
    ->dailyAt('00:05');
