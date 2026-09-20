<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md.
// The first scheduled command registered anywhere in this app.
Schedule::command('platform:check-notifications')->hourly();
