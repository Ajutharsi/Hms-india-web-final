<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Patient;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $totalSent   = NotificationLog::where('status', 'sent')->count();
        $totalFailed = NotificationLog::where('status', 'failed')->count();
        $totalPending = NotificationLog::where('status', 'pending')->count();
        $todayCount  = NotificationLog::whereDate('created_at', today())->count();

        // name is encrypted at rest, so it can't be sorted in SQL — sort after decrypting.
        $patients  = Patient::where('status', 'active')->get(['id', 'name', 'mrn', 'phone', 'email'])->sortBy(fn($p) => $p->name)->values();
        $templates = NotificationTemplate::where('is_active', true)->orderBy('title')->get();

        return view('notifications.index', compact(
            'totalSent', 'totalFailed', 'totalPending', 'todayCount', 'patients', 'templates'
        ));
    }

    public function data()
    {
        $logs = NotificationLog::with('patient')
            ->latest()
            ->get()
            ->map(fn($l) => [
                'id'            => $l->id,
                'patient_name'  => $l->patient->name ?? '—',
                'channel'       => $l->channel,
                'recipient'     => $l->recipient,
                'template'      => $l->template,
                'message'       => $l->message,
                'status'        => $l->status,
                'error_message' => $l->error_message,
                'sent_at'       => $l->sent_at?->format('d M Y h:i A'),
                'created_at'    => $l->created_at->format('d M Y h:i A'),
            ]);

        return response()->json(['data' => $logs]);
    }

    public function send(Request $request, NotificationService $service)
    {
        $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'channel'    => 'required|in:sms,whatsapp,email',
            'recipient'  => 'required|string|max:255',
            'template'   => 'required|exists:notification_templates,name',
        ], $this->validationMessages());

        $variables = [
            'patient_name'   => $request->patient_id ? Patient::find($request->patient_id)?->name : '',
            'hospital_name'  => config('hospital.name'),
            'currency'       => config('hospital.currency'),
        ];

        $sent = $service->send(
            $request->channel,
            $request->recipient,
            $request->template,
            $variables,
            $request->patient_id
        );

        return back()->with($sent ? 'success' : 'error', $sent ? 'Notification sent!' : 'Failed to send notification.');
    }

    public function templates()
    {
        $templates = NotificationTemplate::orderBy('title')->get();
        return view('notifications.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:100|unique:notification_templates,name',
            'title'              => 'required|string|max:255',
            'sms_template'       => 'nullable|string|max:2000',
            'whatsapp_template'  => 'nullable|string|max:2000',
            'email_template'     => 'nullable|string|max:2000',
            'variables'          => 'nullable|string|max:500',
            'is_active'          => 'nullable|boolean',
        ], $this->validationMessages());

        NotificationTemplate::create([
            'name'              => $request->name,
            'title'             => $request->title,
            'sms_template'      => $request->sms_template,
            'whatsapp_template' => $request->whatsapp_template,
            'email_template'    => $request->email_template,
            'variables'         => $request->variables,
            'is_active'         => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Template created!');
    }

    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $request->validate([
            'title'              => 'required|string|max:255',
            'sms_template'       => 'nullable|string|max:2000',
            'whatsapp_template'  => 'nullable|string|max:2000',
            'email_template'     => 'nullable|string|max:2000',
            'variables'          => 'nullable|string|max:500',
            'is_active'          => 'nullable|boolean',
        ], $this->validationMessages());

        $template->update([
            'title'             => $request->title,
            'sms_template'      => $request->sms_template,
            'whatsapp_template' => $request->whatsapp_template,
            'email_template'    => $request->email_template,
            'variables'         => $request->variables,
            'is_active'         => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Template updated!');
    }
}
