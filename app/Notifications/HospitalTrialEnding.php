<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Notifications\Notification;

/** Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md. Database channel only. */
class HospitalTrialEnding extends Notification
{
    public function __construct(private readonly Hospital $hospital)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Trial Ending Soon',
            'message' => "{$this->hospital->name}'s trial ends on {$this->hospital->trial_ends_at->format('d M Y')}.",
            'level' => 'warning',
            'url' => route('super-admin.hospitals.show', $this->hospital),
            'hospital_id' => $this->hospital->id,
            'trial_ends_at' => $this->hospital->trial_ends_at->format('Y-m-d'),
        ];
    }
}
