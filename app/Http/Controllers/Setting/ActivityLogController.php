<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        return view('settings.activity-log');
    }

    public function data(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->module) {
            $query->where('module', $request->module);
        }
        if ($request->action) {
            $query->where('action', $request->action);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->get()->map(fn($l) => [
            'id'          => $l->id,
            'user_name'   => $l->user_name,
            'action'      => $l->action,
            'module'      => $l->module,
            'description' => $l->description,
            'ip_address'  => $l->ip_address,
            'created_at'  => $l->created_at->format('d M Y H:i:s'),
        ]);

        return response()->json(['data' => $logs]);
    }

    public function clear()
    {
        ActivityLog::truncate();
        return redirect()->route('settings.activity-log')
            ->with('success', 'Activity log cleared!');
    }
}