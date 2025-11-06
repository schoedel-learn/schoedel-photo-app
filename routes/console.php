<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send gallery expiration reminders daily at 9 AM
Schedule::job(new \App\Jobs\SendGalleryExpirationReminders)->dailyAt('09:00');
