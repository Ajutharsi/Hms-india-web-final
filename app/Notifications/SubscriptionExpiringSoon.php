<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Notifications\Notification;

/**
 * Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md.
 * Database channel only (no mail/SMS) per SUPER_ADMIN_MASTER_PLAN.md §5.12 —
 * this is a v1 in-app alert, not a delivery-channel expansion of the
 * existing, unrelated, patient-facing NotificationService.
 */
class SubscriptionExpiringSoon extends Notification
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
            'title' => 'Subscription Expiring Soon',
            'message' => "{$this->hospital->name}'s subscription ends on {$this->hospital->subscription_ends_at->format('d M Y')}.",
            'level' => 'warning',
            'url' => route('super-admin.hospitals.show', $this->hospital),
            'hospital_id' => $this->hospital->id,
            'ends_at' => $this->hospital->subscription_ends_at->format('Y-m-d'),
        ];
    }
}
