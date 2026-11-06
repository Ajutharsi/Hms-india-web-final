<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\NotificationLog;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * What the signed-in account can see about itself, whatever its role:
 * its own attendance and leave, its own notifications, and one search
 * box over everything it is allowed to look at.
 *
 * Every other controller is organised by role. These three are not,
 * because the question each answers is "mine", and the answer differs
 * by who is asking rather than by what they do.
 */
class MeController extends Controller
{
    // ------------------------------------------------------- attendance

    public function attendance(Request $request)
    {
        $user = $request->user();

        // Default to this month: the question is almost always "how am I
        // doing this month", and the table grows for ever.
        $month = $request->get('month', now()->format('Y-m'));
        try {
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        } catch (\Exception) {
            $start = now()->startOfMonth();
        }
        $end = (clone $start)->endOfMonth();

        $rows = Attendance::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('attendance_date')
            ->get();

        return response()->json([
            'month' => $start->format('Y-m'),
            'summary' => [
                'present' => $rows->where('status', 'present')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'late' => $rows->where('status', 'late')->count(),
                'half_day' => $rows->where('status', 'half_day')->count(),
                'hours' => round((float) $rows->sum('hours_worked'), 1),
            ],
            'days' => $rows->map(fn ($a) => [
                'id' => $a->id,
                'date' => optional($a->attendance_date)->format('Y-m-d') ?? (string) $a->attendance_date,
                'shift' => $a->shift,
                'check_in' => $a->check_in,
                'check_out' => $a->check_out,
                'status' => $a->status,
                'hours_worked' => (float) $a->hours_worked,
                'notes' => $a->notes,
            ])->all(),
        ]);
    }

    // ------------------------------------------------------------ leave

    public function leaves(Request $request)
    {
        $user = $request->user();

        $rows = LeaveRequest::where('user_id', $user->id)->latest('from_date')->limit(100)->get();

        return response()->json([
            'summary' => [
                'pending' => $rows->where('status', 'pending')->count(),
                'approved' => $rows->where('status', 'approved')->count(),
                'rejected' => $rows->where('status', 'rejected')->count(),
                // Days already taken this year, which is the number
                // people actually want before asking for more.
                'days_taken' => (int) $rows->where('status', 'approved')
                    ->filter(fn ($l) => optional($l->from_date)->year === now()->year)
                    ->sum('days'),
            ],
            'leaves' => $rows->map(fn ($l) => [
                'id' => $l->id,
                'leave_type' => $l->leave_type,
                'from_date' => optional($l->from_date)->format('Y-m-d') ?? (string) $l->from_date,
                'to_date' => optional($l->to_date)->format('Y-m-d') ?? (string) $l->to_date,
                'days' => (int) $l->days,
                'reason' => $l->reason,
                'status' => $l->status,
                'remarks' => $l->remarks,
            ])->all(),
        ]);
    }

    public function leaveStore(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'leave_type' => ['required', 'in:sick,casual,annual,emergency,maternity,paternity'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $from = Carbon::parse($validated['from_date']);
        $to = Carbon::parse($validated['to_date']);

        // Counted here rather than asked for: people get it wrong, and
        // the approver sees this number.
        $days = $from->diffInDays($to) + 1;

        $leave = LeaveRequest::create($validated + [
            'user_id' => $user->id,
            'days' => $days,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => $days === 1 ? 'Leave requested for 1 day.' : "Leave requested for {$days} days.",
            'id' => $leave->id,
        ], 201);
    }

    public function leaveDestroy(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->user_id === $request->user()->id, 403, 'That leave request is not yours.');
        abort_unless($leave->status === 'pending', 422, 'Only a pending request can be withdrawn.');

        $leave->delete();

        return response()->json(['message' => 'Request withdrawn.']);
    }

    // ---------------------------------------------------- notifications

    public function notifications(Request $request)
    {
        $logs = $this->notificationQuery($request)->latest('id')->limit(100)->get();

        return response()->json([
            'unread' => $logs->whereNull('read_at')->count(),
            'notifications' => $logs->map(fn ($n) => [
                'id' => $n->id,
                'channel' => $n->channel,
                'template' => $n->template,
                'message' => $n->message,
                'status' => $n->status,
                'sent_at' => optional($n->sent_at)->format('Y-m-d H:i'),
                'read' => $n->read_at !== null,
            ])->all(),
        ]);
    }

    public function notificationsRead(Request $request)
    {
        $ids = $request->input('ids');

        $query = $this->notificationQuery($request)->whereNull('read_at');
        if (is_array($ids) && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        $marked = $query->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read.', 'marked' => $marked]);
    }

    /**
     * Which log rows belong to the person asking.
     *
     * A patient's are linked by patient_id. Staff have no such link, so
     * theirs are matched on the address the message went to, which is
     * what the sender recorded.
     */
    private function notificationQuery(Request $request)
    {
        $user = $request->user();
        $patient = Patient::where('user_id', $user->id)->first();

        return NotificationLog::query()->where(function ($q) use ($patient, $user) {
            if ($patient !== null) {
                $q->where('patient_id', $patient->id);
            }
            $q->orWhere('recipient', $user->email);
            if (! empty($user->phone)) {
                $q->orWhere('recipient', $user->phone);
            }
        });
    }

    // ----------------------------------------------------------- search

    /**
     * One box over patients, appointments and invoices.
     *
     * Deliberately narrow: it searches the identifiers people actually
     * quote down a phone -- an MRN, an appointment number, an invoice
     * number -- plus the patient's name, which is encrypted and so can
     * only be matched whole, through its hash.
     */
    public function search(Request $request)
    {
        $user = $request->user();
        $term = trim((string) $request->get('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['query' => $term, 'results' => []]);
        }

        $maySeeClinical = $user->hasAnyRole([
            'doctor', 'nurse', 'receptionist', 'lab_assistant', 'pharmacist', 'staff', 'admin', 'super_admin',
        ]);

        if (! $maySeeClinical) {
            return response()->json(['query' => $term, 'results' => []]);
        }

        $nameHash = Patient::hashFor($term);

        $patients = Patient::query()
            ->where('mrn', 'LIKE', "%{$term}%")
            ->when($nameHash, fn ($q) => $q->orWhere('name_hash', $nameHash))
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'type' => 'patient',
                'id' => $p->id,
                'title' => $p->name,
                'subtitle' => $p->mrn,
            ]);

        $appointments = Appointment::with('patient')
            ->where('appointment_id', 'LIKE', "%{$term}%")
            ->latest('appointment_date')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'type' => 'appointment',
                'id' => $a->id,
                'title' => $a->appointment_id,
                'subtitle' => trim(($a->patient?->name ?? '') . ' · ' .
                    (optional($a->appointment_date)->format('Y-m-d') ?? (string) $a->appointment_date)),
            ]);

        $invoices = Invoice::with('patient')
            ->where('invoice_no', 'LIKE', "%{$term}%")
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn ($i) => [
                'type' => 'invoice',
                'id' => $i->id,
                'title' => $i->invoice_no,
                'subtitle' => trim(($i->patient?->name ?? '') . ' · ' . number_format((float) $i->total_amount, 2)),
            ]);

        return response()->json([
            'query' => $term,
            'results' => $patients->concat($appointments)->concat($invoices)->values()->all(),
        ]);
    }
}
