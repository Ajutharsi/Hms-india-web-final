<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Attendance, leave and the notification templates — the last of the
 * admin-only web modules without a mobile route.
 *
 * Approving leave is the one thing here anyone actually wants to do from
 * a phone, so it is a single call rather than a form: the request's own
 * row already carries the dates and the reason.
 */
class AdminHrController extends Controller
{
    use AuthorizesRoles;

    private function authAdmin(Request $request): User
    {
        return $this->authRoles($request, [], 'This account is not an administrator account.');
    }

    // ---------------------------------------------------------- attendance

    public function attendance(Request $request)
    {
        $this->authAdmin($request);

        $date = $request->date ?: now()->toDateString();
        $rows = Attendance::with('user')
            ->whereDate('attendance_date', $date)
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->get();

        return response()->json([
            'date' => $date,
            'totals' => [
                'marked' => $rows->count(),
                'present' => $rows->where('status', 'present')->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'leave' => $rows->where('status', 'leave')->count(),
            ],
            'attendance' => $rows->map(fn ($a) => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'user_name' => $a->user?->name,
                'attendance_date' => $a->attendance_date,
                'shift' => $a->shift,
                'check_in' => $a->check_in,
                'check_out' => $a->check_out,
                'status' => $a->status,
                'hours_worked' => $a->hours_worked,
                'notes' => $a->notes,
            ]),
        ]);
    }

    public function attendanceStore(Request $request)
    {
        $admin = $this->authAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,leave,half_day'],
            'shift' => ['nullable', 'string', 'max:50'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // One row per person per day — marking twice should correct the
        // first mark, not leave two contradictory ones behind.
        $attendance = Attendance::updateOrCreate(
            ['user_id' => $validated['user_id'], 'attendance_date' => $validated['attendance_date']],
            $validated + ['marked_by' => $admin->id],
        );

        return response()->json(['message' => 'Attendance marked.', 'id' => $attendance->id], 201);
    }

    public function attendanceUpdate(Request $request, Attendance $attendance)
    {
        $this->authAdmin($request);

        $attendance->update($request->validate([
            'status' => ['required', 'in:present,absent,leave,half_day'],
            'shift' => ['nullable', 'string', 'max:50'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]));

        return response()->json(['message' => 'Attendance updated.']);
    }

    // --------------------------------------------------------------- leave

    public function leaves(Request $request)
    {
        $this->authAdmin($request);

        $leaves = LeaveRequest::with('user')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json([
            'totals' => [
                'pending' => LeaveRequest::where('status', 'pending')->count(),
                'approved' => LeaveRequest::where('status', 'approved')->count(),
                'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            ],
            'leaves' => $leaves->map(fn ($l) => [
                'id' => $l->id,
                'user_id' => $l->user_id,
                'user_name' => $l->user?->name,
                'leave_type' => $l->leave_type,
                'from_date' => $l->from_date,
                'to_date' => $l->to_date,
                'days' => $l->days,
                'reason' => $l->reason,
                'status' => $l->status,
                'remarks' => $l->remarks,
            ]),
        ]);
    }

    public function leaveStore(Request $request)
    {
        $this->authAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'leave_type' => ['required', 'string', 'max:50'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $days = now()->parse($validated['from_date'])->diffInDays($validated['to_date']) + 1;
        $leave = LeaveRequest::create($validated + ['days' => $days, 'status' => 'pending']);

        return response()->json(['message' => 'Leave request raised.', 'id' => $leave->id], 201);
    }

    public function leaveApprove(Request $request, LeaveRequest $leave)
    {
        $admin = $this->authAdmin($request);
        abort_unless($leave->status === 'pending', 422, 'This request has already been decided.');

        // Optional, but still validated: this was the one write in the
        // admin API taking a field straight off the request, so an
        // over-long remark reached the column instead of the user.
        $validated = $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        $leave->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLog::log('updated', 'HR', "Leave #{$leave->id} approved from the mobile app");

        return response()->json(['message' => 'Leave approved.']);
    }

    public function leaveReject(Request $request, LeaveRequest $leave)
    {
        $admin = $this->authAdmin($request);
        abort_unless($leave->status === 'pending', 422, 'This request has already been decided.');

        $validated = $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        $leave->update(['status' => 'rejected', 'approved_by' => $admin->id, 'remarks' => $validated['remarks']]);

        ActivityLog::log('updated', 'HR', "Leave #{$leave->id} rejected from the mobile app");

        return response()->json(['message' => 'Leave rejected.']);
    }

    public function leaveDestroy(Request $request, LeaveRequest $leave)
    {
        $this->authAdmin($request);
        $leave->delete();

        return response()->json(['message' => 'Leave request deleted.']);
    }

    // ------------------------------------------------------- notifications

    public function notifications(Request $request)
    {
        $this->authAdmin($request);

        $logs = NotificationLog::query()
            ->when($request->channel, fn ($q, $v) => $q->where('channel', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json([
            'totals' => [
                'sent' => NotificationLog::where('status', 'sent')->count(),
                'failed' => NotificationLog::where('status', 'failed')->count(),
            ],
            'logs' => $logs->map(fn ($l) => [
                'id' => $l->id,
                'channel' => $l->channel,
                'recipient' => $l->recipient,
                'template' => $l->template,
                'status' => $l->status,
                'error_message' => $l->error_message,
                'sent_at' => $l->sent_at,
            ]),
        ]);
    }

    public function templates(Request $request)
    {
        $this->authAdmin($request);

        return response()->json([
            'templates' => NotificationTemplate::orderBy('name')->get()->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'title' => $t->title,
                'sms_template' => $t->sms_template,
                'whatsapp_template' => $t->whatsapp_template,
                'email_template' => $t->email_template,
                'variables' => $t->variables,
                'is_active' => (bool) $t->is_active,
            ]),
        ]);
    }

    private function templateRules(?NotificationTemplate $template = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('notification_templates', 'name')->ignore($template?->id)],
            'title' => ['required', 'string', 'max:255'],
            'sms_template' => ['nullable', 'string', 'max:2000'],
            'whatsapp_template' => ['nullable', 'string', 'max:2000'],
            'email_template' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function templateStore(Request $request)
    {
        $this->authAdmin($request);
        $validated = $request->validate($this->templateRules());

        $template = NotificationTemplate::create($validated + ['is_active' => $validated['is_active'] ?? true]);

        return response()->json(['message' => 'Template added.', 'id' => $template->id], 201);
    }

    public function templateUpdate(Request $request, NotificationTemplate $template)
    {
        $this->authAdmin($request);
        $template->update($request->validate($this->templateRules($template)));

        return response()->json(['message' => 'Template updated.']);
    }
}
