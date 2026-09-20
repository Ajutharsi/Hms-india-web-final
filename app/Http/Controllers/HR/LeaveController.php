<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeaveController extends Controller
{
    public function index()
    {
        $pendingCount  = LeaveRequest::where('status', 'pending')->count();
        $approvedCount = LeaveRequest::where('status', 'approved')->count();
        $rejectedCount = LeaveRequest::where('status', 'rejected')->count();
        $totalCount    = LeaveRequest::count();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('hr.leaves.index', compact(
            'pendingCount', 'approvedCount', 'rejectedCount', 'totalCount', 'users'
        ));
    }

    public function data()
    {
        $query = LeaveRequest::with(['user:id,name', 'approvedBy:id,name'])->latest();

        return DataTables::of($query)
            ->addColumn('user_name', fn($l) => $l->user->name ?? 'N/A')
            ->addColumn('approved_by_name', fn($l) => $l->approvedBy->name ?? '—')
            ->editColumn('from_date', fn($l) => $l->from_date?->format('d M Y'))
            ->editColumn('to_date', fn($l) => $l->to_date?->format('d M Y'))
            ->addColumn('actions', fn($l) => $l->status === 'pending'
                ? '<div class="d-flex gap-2"><button onclick="openAction(' . $l->id . ', \'approve\')" class="btn btn-sm btn-icon btn-label-success" title="Approve"><i class="icon-base ti tabler-check"></i></button><button onclick="openAction(' . $l->id . ', \'reject\')" class="btn btn-sm btn-icon btn-label-danger" title="Reject"><i class="icon-base ti tabler-x"></i></button></div>'
                : '—')
            ->filterColumn('user_name', fn($q, $kw) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$kw}%")))
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'leave_type' => 'required|in:sick,casual,annual,emergency,maternity,paternity',
            'from_date'  => 'required|date',
            'to_date'    => 'required|date|after_or_equal:from_date',
            'reason'     => 'required|string|max:1000',
        ], $this->validationMessages());

        $days = \Carbon\Carbon::parse($request->from_date)->diffInDays(\Carbon\Carbon::parse($request->to_date)) + 1;

        LeaveRequest::create([
            'user_id'    => $request->user_id,
            'leave_type' => $request->leave_type,
            'from_date'  => $request->from_date,
            'to_date'    => $request->to_date,
            'days'       => $days,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return back()->with('success', 'Leave request submitted!');
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $leave->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'remarks'     => $request->remarks,
        ]);

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $leave->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'remarks'     => $request->remarks,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(LeaveRequest $leave)
    {
        $leave->delete();
        return response()->json(['success' => true]);
    }
}
