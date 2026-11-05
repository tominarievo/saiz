<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Schedule::command('disaster-intelligence:sync 1')->dailyAt('06:30')->withoutOverlapping();
Schedule::command('disaster-intelligence:sync 5')->dailyAt('06:40')->withoutOverlapping();
Schedule::command('disaster-intelligence:sync 6')->dailyAt('06:50')->withoutOverlapping();
Schedule::command('disaster-intelligence:import-kumamoto-traffic')->everyThreeHours()->withoutOverlapping();
