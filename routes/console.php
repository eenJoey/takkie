<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Herinneringen voor openstaande huishoudtaken (M9). Vereist op de server: elke minuut `php artisan schedule:run`.
Schedule::command('reminders:send')->everyFifteenMinutes()->withoutOverlapping();
