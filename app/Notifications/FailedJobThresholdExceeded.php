<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md. Database channel only. */
class FailedJobThresholdExceeded extends Notification
{
    public function __construct(private readonly int $count)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Failed Job Threshold Exceeded',
            'message' => "{$this->count} queue job(s) have failed — review the Platform Maintenance queue.",
            'level' => 'critical',
            'url' => route('super-admin.maintenance.index'),
            'count' => $this->count,
        ];
    }
}
