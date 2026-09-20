<?php

namespace App\Console\Commands;

use App\Services\NotificationCentreService;
use Illuminate\Console\Command;

/**
 * Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md.
 * The app has no scheduled command registered anywhere before this one
 * (confirmed: routes/console.php only defined `inspire`, bootstrap/app.php
 * has no ->withSchedule()) — this is the first, registered hourly below.
 */
class CheckPlatformNotifications extends Command
{
    protected $signature = 'platform:check-notifications';

    protected $description = 'Check standing platform conditions (subscriptions expiring, trials ending, failed job threshold) and notify super admins of anything new.';

    public function handle(NotificationCentreService $notifications): int
    {
        $results = $notifications->runScheduledChecks();

        foreach ($results as $check => $sent) {
            $this->components->info("{$check}: {$sent} notification(s) sent");
        }

        return self::SUCCESS;
    }
}
