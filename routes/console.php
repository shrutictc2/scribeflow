<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the recordings table (and the files behind it) from growing forever.
// In production this is what you'd point Laravel's scheduler at via a
// single cron entry (`* * * * * php artisan schedule:run`), or hand off
// to a queue worker supervisor / Laravel Horizon.
Schedule::command('recordings:purge')->daily();
