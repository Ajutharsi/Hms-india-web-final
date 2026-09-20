<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mirrors PatientAttachment's shape/storage convention — see SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md. */
class SupportTicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id', 'reply_id', 'file_name', 'file_path',
        'file_type', 'file_size', 'description', 'uploaded_by',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function reply()
    {
        return $this->belongsTo(SupportTicketReply::class, 'reply_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
