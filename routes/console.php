<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('logs:aggregate-daily')->dailyAt('23:59')->description('Aggregate hourly logs into daily targets');
Schedule::command('logs:aggregate-daily')->dailyAt('00:01')->description('Aggregate hourly logs into daily targets (midnight aggregate)');
