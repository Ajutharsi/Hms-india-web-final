<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A customer support ticket — the Super Admin Support Centre's core record.
 * See SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md.
 */
class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['open', 'assigned', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
    public const CATEGORIES = ['technical', 'billing', 'account', 'feature_request', 'bug', 'other'];

    protected $fillable = [
        'ticket_number', 'hospital_id', 'subject', 'description',
        'contact_name', 'contact_email', 'priority', 'category', 'status',
        'assigned_to', 'created_by', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $ticket) {
            $last   = static::withTrashed()->latest('id')->first();
            $number = $last ? $last->id + 1 : 1;
            do {
                $candidate = 'TKT-' . str_pad($number, 5, '0', STR_PAD_LEFT);
                $number++;
            } while (static::withTrashed()->where('ticket_number', $candidate)->exists());

            $ticket->ticket_number = $candidate;
        });
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id')->orderBy('created_at');
    }

    public function publicReplies()
    {
        return $this->replies()->where('is_internal', false);
    }

    public function internalNotes()
    {
        return $this->replies()->where('is_internal', true);
    }

    public function attachments()
    {
        return $this->hasMany(SupportTicketAttachment::class, 'ticket_id');
    }

    /** This ticket's own audit trail — see ActivityLog::log() call sites in SupportTicketController. */
    public function activityLogs()
    {
        return ActivityLog::where('model_type', self::class)->where('model_id', $this->id)->latest();
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['resolved', 'closed'])) {
            return false;
        }

        return $this->created_at->lt(now()->subHours(config('hospital.ticket_overdue_hours', 48)));
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed'])
            ->where('created_at', '<', now()->subHours(config('hospital.ticket_overdue_hours', 48)));
    }

    /**
     * Average hours between creation and resolution, across every ticket
     * that has actually been resolved — null (not zero) when nothing has
     * been resolved yet, so the UI can show "Not tracked yet" instead of a
     * fabricated "0 hours". One aggregate query, MySQL-only (matches the
     * existing systemHealth()/DB-size convention elsewhere in this app).
     */
    public static function averageResolutionHours(): ?float
    {
        try {
            $avg = static::withTrashed()
                ->whereNotNull('resolved_at')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
                ->value('avg_hours');

            return $avg !== null ? round((float) $avg, 1) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
