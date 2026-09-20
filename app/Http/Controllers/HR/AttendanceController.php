<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    public function index()
    {
        $todayPresent = Attendance::whereDate('attendance_date', today())->where('status', 'present')->count();
        $todayAbsent  = Attendance::whereDate('attendance_date', today())->where('status', 'absent')->count();
        $todayLate    = Attendance::whereDate('attendance_date', today())->where('status', 'late')->count();
        $onLeaveToday = Attendance::whereDate('attendance_date', today())->where('status', 'leave')->count();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('hr.attendance.index', compact(
            'todayPresent', 'todayAbsent', 'todayLate', 'onLeaveToday', 'users'
        ));
    }

    public function data()
    {
        $query = Attendance::with('user:id,name')->latest('attendance_date');

        return DataTables::of($query)
            ->addColumn('user_name', fn($a) => $a->user->name ?? 'N/A')
            ->editColumn('attendance_date', fn($a) => $a->attendance_date?->format('d M Y'))
            ->editColumn('check_in', fn($a) => $a->check_in?->format('h:i A'))
            ->editColumn('check_out', fn($a) => $a->check_out?->format('h:i A'))
            ->filterColumn('user_name', fn($q, $kw) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$kw}%")))
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'         => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'shift'           => 'required|in:morning,afternoon,night,full_day',
            'status'          => 'required|in:present,absent,late,half_day,leave',
            'check_in'        => 'nullable|date_format:H:i',
            'check_out'       => 'nullable|date_format:H:i',
            'notes'           => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $checkIn  = $request->check_in ? $request->attendance_date . ' ' . $request->check_in : null;
        $checkOut = $request->check_out ? $request->attendance_date . ' ' . $request->check_out : null;

        $hoursWorked = null;
        if ($checkIn && $checkOut) {
            $hoursWorked = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
        }

        Attendance::create([
            'user_id'         => $request->user_id,
            'attendance_date' => $request->attendance_date,
            'shift'           => $request->shift,
            'check_in'        => $checkIn,
            'check_out'       => $checkOut,
            'status'          => $request->status,
            'hours_worked'    => $hoursWorked,
            'notes'           => $request->notes,
            'marked_by'       => auth()->id(),
        ]);

        return back()->with('success', 'Attendance marked successfully!');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $request->validate([
            'shift'     => 'required|in:morning,afternoon,night,full_day',
            'status'    => 'required|in:present,absent,late,half_day,leave',
            'check_in'  => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'notes'     => 'nullable|string|max:1000',
        ], $this->validationMessages());

        $checkIn  = $request->check_in ? $attendance->attendance_date->toDateString() . ' ' . $request->check_in : null;
        $checkOut = $request->check_out ? $attendance->attendance_date->toDateString() . ' ' . $request->check_out : null;

        $hoursWorked = null;
        if ($checkIn && $checkOut) {
            $hoursWorked = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
        }

        $attendance->update([
            'shift'        => $request->shift,
            'status'       => $request->status,
            'check_in'     => $checkIn,
            'check_out'    => $checkOut,
            'hours_worked' => $hoursWorked,
            'notes'        => $request->notes,
        ]);

        return back()->with('success', 'Attendance updated successfully!');
    }
}
