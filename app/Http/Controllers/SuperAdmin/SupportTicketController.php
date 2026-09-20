<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Services\NotificationCentreService;
use App\Services\NotificationService;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

/**
 * Super Admin Support Centre — see SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md.
 * Follows the exact DataTables-list + offcanvas-create/edit pattern already
 * established by HospitalController.
 */
class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $service,
        private readonly NotificationService $notifier,
        private readonly NotificationCentreService $notificationCentre,
    ) {
    }

    private function assignableStaff()
    {
        return User::role(['super_admin', 'admin'])->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function index(Request $request)
    {
        return view('super-admin.support.index', [
            'kpis'            => $this->service->kpis(),
            'recentlyUpdated' => $this->service->recentlyUpdated(),
            'hospitals'       => Hospital::orderBy('name')->get(['id', 'name']),
            'staff'           => $this->assignableStaff(),
            'statusFilter'    => $request->get('status'),
            'priorityFilter'  => $request->get('priority'),
            'categoryFilter'  => $request->get('category'),
            'overdueFilter'   => $request->get('overdue'),
        ]);
    }

    public function data(Request $request)
    {
        $query = $request->get('status') === 'archived'
            ? SupportTicket::onlyTrashed()
            : SupportTicket::query();

        $query->with(['hospital:id,name', 'assignedStaff:id,name']);

        if ($request->filled('status') && $request->get('status') !== 'archived') {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('hospital_id')) {
            $query->where('hospital_id', $request->get('hospital_id'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->get('assigned_to'));
        }
        if ($request->filled('contact')) {
            $query->where('contact_name', 'like', '%' . $request->get('contact') . '%');
        }
        if ($request->get('overdue') === '1') {
            $query->overdue();
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->get('to_date'));
        }

        return DataTables::of($query)
            ->addColumn('priority_badge', function (SupportTicket $t) {
                $colors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
                return '<span class="badge bg-label-' . ($colors[$t->priority] ?? 'secondary') . '">' . ucfirst($t->priority) . '</span>';
            })
            ->addColumn('status_badge', function (SupportTicket $t) {
                if ($t->trashed()) {
                    return '<span class="badge bg-label-secondary">Archived</span>';
                }
                $colors = [
                    'open' => 'primary', 'assigned' => 'info', 'in_progress' => 'warning',
                    'waiting_customer' => 'warning', 'resolved' => 'success', 'closed' => 'secondary',
                ];
                $label = ucwords(str_replace('_', ' ', $t->status));
                $overdue = $t->isOverdue() ? ' <span class="badge bg-label-danger">Overdue</span>' : '';
                return '<span class="badge bg-label-' . ($colors[$t->status] ?? 'secondary') . '">' . $label . '</span>' . $overdue;
            })
            ->addColumn('hospital_name', fn (SupportTicket $t) => $t->hospital?->name ?? '—')
            ->addColumn('assigned_name', fn (SupportTicket $t) => $t->assignedStaff?->name ?? 'Unassigned')
            ->addColumn('created_display', fn (SupportTicket $t) => $t->created_at->format('d M Y H:i'))
            ->addColumn('updated_display', fn (SupportTicket $t) => $t->updated_at->diffForHumans())
            ->addColumn('actions', fn (SupportTicket $t) => '
                <a href="' . route('super-admin.support.show', $t->id) . '" class="btn btn-sm btn-icon btn-label-info" title="View">
                    <i class="icon-base ti tabler-eye"></i>
                </a>
            ')
            ->rawColumns(['priority_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    private function validationRules(): array
    {
        return [
            'hospital_id'   => 'required|exists:hospitals,id',
            'subject'       => 'required|string|max:255',
            'description'   => 'required|string',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'priority'      => 'required|in:' . implode(',', SupportTicket::PRIORITIES),
            'category'      => 'required|in:' . implode(',', SupportTicket::CATEGORIES),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());

        $hospital = Hospital::find($validated['hospital_id']);
        $validated['contact_name']  = ($validated['contact_name']  ?? null) ?: $hospital?->contact_person;
        $validated['contact_email'] = ($validated['contact_email'] ?? null) ?: $hospital?->email;
        $validated['created_by']    = auth()->id();

        $ticket = SupportTicket::create($validated);

        ActivityLog::log('ticket_created', 'support_ticket', "Ticket {$ticket->ticket_number} created for {$hospital?->name}: {$ticket->subject}", $ticket);

        $this->notificationCentre->notifyNewSupportTicket($ticket);

        return redirect()->route('super-admin.support.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_number} created.");
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['hospital', 'assignedStaff', 'creator', 'attachments.uploadedBy']);
        $replies = $ticket->replies()->with('user')->get();

        return view('super-admin.support.show', [
            'ticket'        => $ticket,
            'publicReplies' => $replies->where('is_internal', false)->values(),
            'internalNotes' => $replies->where('is_internal', true)->values(),
            'activityLog'   => $ticket->activityLogs()->with('user')->take(50)->get(),
            'staff'         => $this->assignableStaff(),
        ]);
    }

    public function getJson(SupportTicket $ticket)
    {
        return response()->json($ticket);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate($this->validationRules());

        $ticket->update($validated);

        ActivityLog::log('ticket_updated', 'support_ticket', "Ticket {$ticket->ticket_number} details updated", $ticket);

        return redirect()->route('super-admin.support.show', $ticket)->with('success', 'Ticket updated.');
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $request->validate(['assigned_to' => 'required|exists:users,id']);

        $staff = User::find($request->assigned_to);
        $ticket->assigned_to = $staff->id;
        if ($ticket->status === 'open') {
            $ticket->status = 'assigned';
        }
        $ticket->save();

        ActivityLog::log('assignment_changed', 'support_ticket', "Ticket {$ticket->ticket_number} assigned to {$staff->name}", $ticket);

        return back()->with('success', "Assigned to {$staff->name}.");
    }

    private function applyStatus(SupportTicket $ticket, string $status): void
    {
        $old = $ticket->status;
        $ticket->status = $status;

        if ($status === 'resolved' && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();
        }
        if ($status === 'closed') {
            $ticket->closed_at = now();
            $ticket->resolved_at ??= now();
        }
        if (in_array($status, ['open', 'assigned', 'in_progress', 'waiting_customer'])) {
            // Reopening or otherwise moving back to an active state — clear
            // the terminal timestamps so a later resolve/close is measured
            // from a fresh point, not the original (now-void) one.
            $ticket->resolved_at = null;
            $ticket->closed_at = null;
        }

        $ticket->save();

        $label = ucwords(str_replace('_', ' ', $status));
        $oldLabel = ucwords(str_replace('_', ' ', $old));
        ActivityLog::log('status_changed', 'support_ticket', "Ticket {$ticket->ticket_number} status changed from {$oldLabel} to {$label}", $ticket);
    }

    public function setStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate(['status' => 'required|in:' . implode(',', SupportTicket::STATUSES)]);

        $this->applyStatus($ticket, $request->status);

        return back()->with('success', 'Status updated.');
    }

    public function close(SupportTicket $ticket)
    {
        $this->applyStatus($ticket, 'closed');
        ActivityLog::log('ticket_closed', 'support_ticket', "Ticket {$ticket->ticket_number} closed", $ticket);

        return back()->with('success', 'Ticket closed.');
    }

    public function reopen(SupportTicket $ticket)
    {
        $this->applyStatus($ticket, 'open');
        ActivityLog::log('ticket_reopened', 'support_ticket', "Ticket {$ticket->ticket_number} reopened", $ticket);

        return back()->with('success', 'Ticket reopened.');
    }

    public function setPriority(Request $request, SupportTicket $ticket)
    {
        $request->validate(['priority' => 'required|in:' . implode(',', SupportTicket::PRIORITIES)]);

        $old = $ticket->priority;
        $ticket->update(['priority' => $request->priority]);

        ActivityLog::log('priority_changed', 'support_ticket', "Ticket {$ticket->ticket_number} priority changed from " . ucfirst($old) . ' to ' . ucfirst($request->priority), $ticket);

        return back()->with('success', 'Priority updated.');
    }

    public function addNote(Request $request, SupportTicket $ticket)
    {
        $request->validate(['body' => 'required|string']);

        $ticket->replies()->create([
            'user_id'     => auth()->id(),
            'body'        => $request->body,
            'is_internal' => true,
        ]);

        ActivityLog::log('note_added', 'support_ticket', "Internal note added to ticket {$ticket->ticket_number}", $ticket);

        return back()->with('success', 'Internal note added.');
    }

    /**
     * Public reply — visible to (and, unlike internal notes, actually sent
     * to) the hospital contact. Reuses NotificationService rather than any
     * new delivery mechanism; see SUPER_ADMIN_SUPPORT_CENTRE_REPORT.md for
     * the 'ticket_reply' template.
     */
    public function addReply(Request $request, SupportTicket $ticket)
    {
        $request->validate(['body' => 'required|string']);

        $reply = $ticket->replies()->create([
            'user_id'     => auth()->id(),
            'body'        => $request->body,
            'is_internal' => false,
        ]);

        ActivityLog::log('reply_added', 'support_ticket', "Public reply added to ticket {$ticket->ticket_number}", $ticket);

        if ($ticket->contact_email) {
            $this->notifier->send('email', $ticket->contact_email, 'ticket_reply', [
                'ticket_number' => $ticket->ticket_number,
                'subject'       => $ticket->subject,
                'reply'         => $reply->body,
            ]);
        }

        return back()->with('success', 'Reply sent.');
    }

    public function uploadAttachment(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'file'        => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,csv,log,txt|max:10240',
            'description' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs("support-tickets/{$ticket->id}/attachments", $fileName, 'public');

        SupportTicketAttachment::create([
            'ticket_id'   => $ticket->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_type'   => $file->getMimeType(),
            'file_size'   => round($file->getSize() / 1024, 2) . ' KB',
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ]);

        ActivityLog::log('attachment_added', 'support_ticket', "File attached to ticket {$ticket->ticket_number}: {$file->getClientOriginalName()}", $ticket);

        return back()->with('success', 'File attached.');
    }

    public function downloadAttachment(SupportTicketAttachment $attachment)
    {
        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /** Archive (soft delete) — never a hard delete, per the task's explicit instruction. */
    public function archive(SupportTicket $ticket)
    {
        if (! in_array($ticket->status, ['resolved', 'closed'])) {
            return response()->json(['error' => 'Only resolved or closed tickets can be archived. Close or resolve this ticket first.'], 422);
        }

        ActivityLog::log('ticket_archived', 'support_ticket', "Ticket {$ticket->ticket_number} archived", $ticket);
        $ticket->delete();

        return response()->json(['success' => true]);
    }

    public function restore($id)
    {
        $ticket = SupportTicket::onlyTrashed()->findOrFail($id);
        $ticket->restore();

        ActivityLog::log('ticket_restored', 'support_ticket', "Ticket {$ticket->ticket_number} restored from the archive", $ticket);

        return response()->json(['success' => true]);
    }
}
