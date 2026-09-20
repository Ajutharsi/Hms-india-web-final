<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Notifications\Notification;

/**
 * Super Admin Notification Centre — see SUPER_ADMIN_NOTIFICATION_CENTRE_REPORT.md.
 * Dispatched synchronously (not on a schedule) the moment a ticket is
 * created — see SupportTicketController::store(). Database channel only.
 */
class NewSupportTicketCreated extends Notification
{
    public function __construct(private readonly SupportTicket $ticket)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Support Ticket',
            'message' => ucfirst($this->ticket->priority) . " priority ticket {$this->ticket->ticket_number}: {$this->ticket->subject}",
            'level' => in_array($this->ticket->priority, ['high', 'critical']) ? 'critical' : 'info',
            'url' => route('super-admin.support.show', $this->ticket),
            'ticket_id' => $this->ticket->id,
        ];
    }
}
